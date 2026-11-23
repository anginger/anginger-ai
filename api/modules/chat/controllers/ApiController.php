<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\modules\chat\controllers;

use anginger\controllers\BaseController;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Chat API.
 *
 *   GET  /api/chat -> {"model": "..."}                     (200)
 *   POST /api/chat -> SSE stream proxied from OpenAI        (text/event-stream)
 *
 * POST body (JSON): {"messages": [{"role": "user", "content": "..."}, ...]}
 * The API key never leaves the server; the browser only talks to /api/chat.
 */
class ApiController extends BaseController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET', 'POST'],
                ],
            ],
        ]);
    }

    public function actionIndex(): Response
    {
        if (!$this->requireLogin()) {
            return $this->forbidden();
        }
        $request = Yii::$app->request;

        if ($request->method === 'GET') {
            return $this->asJson(['model' => Yii::$app->params['openai']['model'] ?? '']);
        }

        $messages = $this->sanitizeMessages($request->getBodyParams()['messages'] ?? null);
        if ($messages === null) {
            return $this->badRequest();
        }

        $openai = Yii::$app->params['openai'] ?? [];
        $key = $openai['key'] ?? null;
        $model = $openai['model'] ?? 'gpt-4o-mini';
        $baseUrl = $openai['baseUrl'] ?? 'https://api.openai.com/v1';
        if (empty($key)) {
            Yii::$app->response->setStatusCode(500);
            return $this->asJson(['message' => 'openai api key is not configured (set OPENAI_API_KEY or config.local.php)']);
        }

        return $this->streamCompletion($baseUrl, $key, $model, $messages);
    }

    /**
     * Keep only well-formed chat messages (role + non-empty content).
     *
     * @return array[]|null null when the input is invalid
     */
    private function sanitizeMessages(mixed $messages): ?array
    {
        if (!is_array($messages) || count($messages) === 0 || count($messages) > 200) {
            return null;
        }
        $clean = [];
        foreach ($messages as $m) {
            if (!is_array($m) || !isset($m['role'], $m['content'])) {
                continue;
            }
            $role = in_array($m['role'], ['system', 'user', 'assistant'], true) ? $m['role'] : 'user';
            $content = trim(strval($m['content']));
            if ($content === '') {
                continue;
            }
            $clean[] = ['role' => $role, 'content' => $content];
        }
        return $clean ?: null;
    }

    /**
     * Proxy a streaming chat completion from OpenAI to the client as SSE.
     */
    private function streamCompletion(string $baseUrl, string $key, string $model, array $messages): Response
    {
        // defeat output buffering so chunks reach the browser immediately
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        $status = 0;
        $headersSent = false;
        $errorBody = '';

        $ch = curl_init($baseUrl . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $key,
                'Accept: text/event-stream',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'messages' => $messages,
                'stream' => true,
            ]),
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$status, &$headersSent) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#i', $header, $m)) {
                    $status = (int)$m[1];
                }
                if ($status >= 200 && $status < 300 && !$headersSent) {
                    header('Content-Type: text/event-stream; charset=utf-8');
                    header('Cache-Control: no-cache');
                    header('X-Accel-Buffering: no');
                    $headersSent = true;
                }
                return strlen($header);
            },
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$headersSent, &$errorBody) {
                if ($headersSent) {
                    echo $data;
                    flush();
                } else {
                    // non-2xx: buffer the JSON error body instead of streaming it
                    $errorBody .= $data;
                }
                return strlen($data);
            },
        ]);
        $ok = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($headersSent) {
            if (!$ok && $err !== '') {
                echo 'data: ' . json_encode(['error' => ['message' => $err]]) . "\n\n";
                flush();
            }
            echo "data: [DONE]\n\n";
            flush();
            Yii::$app->response->isSent = true;
            return Yii::$app->response;
        }

        // error path: OpenAI answered with a non-2xx JSON body
        $message = 'openai request failed';
        $decoded = json_decode($errorBody, true);
        if (is_array($decoded) && isset($decoded['error']['message'])) {
            $message = strval($decoded['error']['message']);
        } elseif (!$ok && $err !== '') {
            $message = $err;
        }
        Yii::$app->response->setStatusCode($status >= 400 ? $status : 502);
        return $this->asJson(['message' => $message]);
    }
}
