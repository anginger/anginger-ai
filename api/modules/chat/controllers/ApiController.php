<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\modules\chat\controllers;

use anginger\controllers\BaseController;
use anginger\models\Conversation;
use anginger\models\Message;
use anginger\models\User;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Chat API. The browser only talks to this controller; the OpenAI key
 * never leaves the server.
 *
 *   GET    /api/chat                      -> {"model": "..."}
 *   POST   /api/chat                      -> SSE stream of a completion
 *                                             body: {"conversation_uuid"?: "...", "messages": [...]}
 *                                             (without conversation_uuid a new conversation is created)
 *   GET    /api/chat/conversations        -> list my conversations
 *   POST   /api/chat/conversations        -> create {title?, system_prompt?}
 *   GET    /api/chat/conversation?uuid=   -> conversation + full message history (context)
 *   PUT    /api/chat/conversation?uuid=   -> update {title?, system_prompt?}
 *   DELETE /api/chat/conversation?uuid=   -> delete conversation + its messages
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
                    'conversations' => ['GET', 'POST'],
                    'conversation' => ['GET', 'PUT', 'DELETE'],
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
            return $this->asJson([
                'model' => Yii::$app->params['openai']['model'] ?? '',
                'stream' => (bool)(Yii::$app->params['openai']['stream'] ?? true),
            ]);
        }

        $body = $request->getBodyParams();
        $newMessages = $this->sanitizeMessages($body['messages'] ?? null);
        if ($newMessages === null) {
            return $this->badRequest();
        }

        $conversation = $this->resolveConversation($body['conversation_uuid'] ?? null, $newMessages);
        if ($conversation instanceof Response) {
            return $conversation; // error response
        }

        // server-side context: system prompt + stored history + new messages
        $context = [];
        if (!empty($conversation->system_prompt)) {
            $context[] = ['role' => 'system', 'content' => $conversation->system_prompt];
        }
        foreach ($conversation->messages as $m) {
            $context[] = ['role' => $m->role, 'content' => $m->content];
        }
        $context = array_merge($context, $newMessages);
        // cap the context window: system prompt + latest 30 turns
        if (count($context) > 31) {
            $sys = array_slice($context, 0, 1);
            $context = array_merge($sys, array_slice($context, -30));
        }

        // persist the new user message(s) before streaming; a retry carrying the
        // same client_id must not create a duplicate row (idempotent re-send)
        foreach ($newMessages as $m) {
            if (!empty($m['client_id'])
                && Message::find()->where(['conversation_uuid' => $conversation->uuid, 'client_id' => $m['client_id']])->exists()) {
                continue;
            }
            $msg = new Message();
            $msg->conversation_uuid = $conversation->uuid;
            $msg->role = $m['role'];
            $msg->content = $m['content'];
            $msg->created_time = time();
            if (!empty($m['client_id'])) {
                $msg->client_id = $m['client_id'];
            }
            $msg->save(false);
        }

        $openai = Yii::$app->params['openai'] ?? [];
        $key = $openai['key'] ?? null;
        if (trim(strval($key)) === '') {
            Yii::$app->response->setStatusCode(503);
            return $this->asJson(['message' => '未設定 OPENAI_API_KEY，請聯繫管理員']);
        }
        $model = $openai['model'] ?? 'gpt-4o-mini';
        $baseUrl = $openai['baseUrl'] ?? 'https://api.openai.com/v1';
        if (empty($key)) {
            Yii::$app->response->setStatusCode(500);
            return $this->asJson(['message' => 'openai api key is not configured (set OPENAI_API_KEY or config.local.php)']);
        }

        $stream = (bool)($openai['stream'] ?? true);
        return $this->streamCompletion($baseUrl, $key, $model, $context, $conversation, $stream);
    }

    /**
     * GET /api/chat/conversations — my conversations, newest activity first.
     */
    public function actionConversations(): Response
    {
        if (!$this->requireLogin()) {
            return $this->forbidden();
        }
        if (Yii::$app->request->method === 'POST') {
            $body = Yii::$app->request->getBodyParams();
            $conv = new Conversation();
            $conv->uuid = Uuid::uuid7()->toString();
            $conv->user_uuid = Yii::$app->user->identity->uuid;
            $conv->title = mb_substr(trim(strval($body['title'] ?? '')), 0, 200) ?: '新對話';
            $conv->system_prompt = trim(strval($body['system_prompt'] ?? '')) ?: null;
            $conv->created_time = time();
            $conv->updated_time = $conv->created_time;
            if (!$conv->save()) {
                Yii::$app->response->setStatusCode(400);
                return $this->asJson(["message" => "validation failed", "errors" => $conv->errors]);
                return $this->serverError();
            }
            Yii::$app->response->setStatusCode(201);
            return $this->asJson($conv->toArray());
        }

        $userUuid = Yii::$app->user->identity->uuid;
        $rows = Conversation::find()
            ->where(['user_uuid' => $userUuid])
            ->orderBy(['updated_time' => SORT_DESC])
            ->asArray()
            ->all();
        foreach ($rows as &$row) {
            $row['message_count'] = Message::find()->where(['conversation_uuid' => $row['uuid']])->count();
        }
        unset($row);
        return $this->asJson($rows);
    }

    /**
     * GET/PUT/DELETE /api/chat/conversation?uuid=...
     */
    public function actionConversation(): Response
    {
        if (!$this->requireLogin()) {
            return $this->forbidden();
        }
        $request = Yii::$app->request;
        $uuid = $request->getQueryParam('uuid', $request->getBodyParam('uuid'));
        if (!is_scalar($uuid) || $uuid === '') {
            return $this->badRequest();
        }
        $conv = $this->findOwnConversation($uuid);
        if ($conv === null) {
            return $this->notFound();
        }

        switch ($request->method) {
            case 'GET':
                $messages = array_map(fn (Message $m) => [
                    'role' => $m->role,
                    'content' => $m->content,
                    'created_time' => (int)$m->created_time,
                ], $conv->messages);
                return $this->asJson($conv->toArray() + ['messages' => $messages]);

            case 'PUT':
                $body = $request->getBodyParams();
                if (isset($body['title'])) {
                    $conv->title = mb_substr(trim(strval($body['title'])), 0, 200) ?: $conv->title;
                }
                if (array_key_exists('system_prompt', $body)) {
                    $conv->system_prompt = trim(strval($body['system_prompt'])) ?: null;
                }
                $conv->updated_time = time();
                if (!$conv->save()) {
                    Yii::$app->response->setStatusCode(400);
                    return $this->asJson(["message" => "validation failed", "errors" => $conv->errors]);
                }
                return $this->emptyResponse(204);

            case 'DELETE':
                Message::deleteAll(['conversation_uuid' => $conv->uuid]);
                $conv->delete();
                return $this->emptyResponse(204);
        }
        return $this->badRequest();
    }

    /**
     * Find a conversation by uuid (auto-creating one when $uuid is null),
     * scoped to the current user. Returns a Response on error.
     *
     * @return Conversation|Response
     */
    private function resolveConversation(mixed $uuid, array $newMessages): Conversation|Response
    {
        $userUuid = Yii::$app->user->identity->uuid;
        if (!is_scalar($uuid) && $uuid !== null) {
            return $this->badRequest(); // e.g. {"conversation_uuid": ["x"]} would TypeError on ?string
        }
        $uuid = $uuid === null ? null : strval($uuid);
        if (!empty($uuid)) {
            $conv = $this->findOwnConversation(strval($uuid));
            return $conv ?? $this->notFound();
        }
        // no conversation given: create one, titled after the first message
        $conv = new Conversation();
        $conv->uuid = Uuid::uuid7()->toString();
        $conv->user_uuid = $userUuid;
        $first = '';
        foreach ($newMessages as $m) {
            if ($m['role'] === 'user') {
                $first = $m['content'];
                break;
            }
        }
        $conv->title = mb_substr($first, 0, 30) ?: '新對話';
        $conv->system_prompt = null;
        $conv->created_time = time();
        $conv->updated_time = $conv->created_time;
        if (!$conv->save(false)) {
            return $this->serverError();
        }
        return $conv;
    }

    private function findOwnConversation(string $uuid): ?Conversation
    {
        return Conversation::findOne(['uuid' => $uuid, 'user_uuid' => Yii::$app->user->identity->uuid]);
    }

    /**
     * Keep only well-formed chat messages (role + non-empty content).
     *
     * @return array[]|null null when the input is invalid
     */
    private function sanitizeMessages(mixed $messages): ?array
    {
        if (!is_array($messages) || count($messages) === 0 || count($messages) > 50) {
            return null;
        }
        $clean = [];
        foreach ($messages as $m) {
            if (!is_array($m) || !isset($m['role'], $m['content'])) {
                continue;
            }
            $role = 'user'; // client may never inject system/assistant messages (prompt injection)
            $content = trim(strval($m['content']));
            if ($content === '') {
                continue;
            }
            $msg = ['role' => $role, 'content' => $content];
            // optional idempotency key for retries (validated, never echoed raw)
            if (isset($m['client_id']) && is_scalar($m['client_id'])) {
                $cid = strval($m['client_id']);
                if ($cid !== '' && strlen($cid) <= 36) {
                    $msg['client_id'] = $cid;
                }
            }
            $clean[] = $msg;
        }
        return $clean ?: null;
    }

    /**
     * Proxy a streaming chat completion from OpenAI to the client as SSE,
     * then persist the assistant reply as the conversation context.
     */
    private function streamCompletion(string $baseUrl, string $key, string $model, array $messages, Conversation $conversation, bool $stream = true): Response
    {
        // defeat output buffering so chunks reach the browser immediately
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        $status = 0;
        $headersSent = false;
        $errorBody = '';
        $sseBody = '';

        if ($stream) {
            // live SSE pass; on a non-2xx the body is buffered in $errorBody
            $ok = $this->postCompletion($baseUrl, $key, $model, $messages, true, $status, $headersSent, $sseBody, $errorBody, $conversation);
            if ($headersSent) {
                if ($this->extractStreamedText($sseBody) === '') {
                    // 2xx but an empty stream (some gateways do this) — fall
                    // through to the non-streaming retry below; headers are
                    // already sent, so the synthetic chunk is just appended
                    $headersSent = true;
                } else {
                    echo "data: [DONE]\n\n";
                    flush();
                    $this->persistReply($conversation, $this->extractStreamedText($sseBody));
                    Yii::$app->response->isSent = true;
                    return Yii::$app->response;
                }
            } elseif (!$ok) {
                $errorBody = $errorBody !== '' ? $errorBody : 'curl error';
            }
        }

        // Non-streaming mode, or streaming failed/returned empty: one plain
        // POST, then wrap the full completion as a single synthetic SSE chunk
        // so the frontend's SSE parser keeps working unchanged.
        $status2 = 0;
        $fullBody = '';
        $this->postCompletion($baseUrl, $key, $model, $messages, false, $status2, $dummySent, $dummySse, $fullBody, null);
        $full = json_decode($fullBody, true);
        $text = $full['choices'][0]['message']['content'] ?? null;
        if ($status2 >= 200 && $status2 < 300 && is_string($text) && $text !== '') {
            $chunk = json_encode(['choices' => [['index' => 0, 'delta' => ['role' => 'assistant', 'content' => $text]]]]);
            $sseBody = 'data: ' . $chunk . "\n\n";
            if (!$headersSent) {
                header('Content-Type: text/event-stream; charset=utf-8');
                header('Cache-Control: no-cache');
                header('X-Accel-Buffering: no');
                header('X-Conversation-Uuid: ' . $conversation->uuid);
            }
            echo $sseBody;
            echo "data: [DONE]\n\n";
            flush();
            $this->persistReply($conversation, $text);
            Yii::$app->response->isSent = true;
            return Yii::$app->response;
        }

        // error path: the upstream answered with a non-2xx JSON body
        $errorBody = $fullBody !== '' ? $fullBody : $errorBody;
        $status = $status2 >= 400 ? $status2 : ($status >= 400 ? $status : 502);
        $message = 'openai request failed';
        $decoded = json_decode($errorBody, true);
        if (is_array($decoded) && isset($decoded['error']['message'])) {
            Yii::error('OpenAI upstream error: ' . strval($decoded['error']['message'])); // detail stays server-side
        }
        $message = '上游 AI 服務發生錯誤，請稍後再試';
        if ($headersSent) {
            // SSE headers already went out (empty stream case) — report as a chunk
            echo 'data: ' . json_encode(['error' => ['message' => $message]]) . "\n\n";
            echo "data: [DONE]\n\n";
            flush();
            Yii::$app->response->isSent = true;
            return Yii::$app->response;
        }
        Yii::$app->response->setStatusCode($status);
        return $this->asJson(['message' => $message]);
    }

    /**
     * Persist the assistant reply so the conversation context survives reloads.
     */
    private function persistReply(Conversation $conversation, string $text): void
    {
        if ($text === '') {
            return;
        }
        $msg = new Message();
        $msg->conversation_uuid = $conversation->uuid;
        $msg->role = 'assistant';
        $msg->content = $text;
        $msg->created_time = time();
        $msg->save(false);
        Conversation::updateAll(['updated_time' => time()], ['uuid' => $conversation->uuid]);
    }

    /**
     * POST /chat/completions. When $stream is true and the upstream answers
     * 2xx, SSE headers are sent immediately and chunks are echoed live while
     * being buffered into $sseBody; otherwise the raw body lands in $errorBody.
     */
    private function postCompletion(string $baseUrl, string $key, string $model, array $messages, bool $stream, ?int &$status, ?bool &$sent, ?string &$sseBody, ?string &$errorBody, ?Conversation $conversation): bool
    {
        $status = 0;
        $sent = false;
        $sseBody = '';
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
                'stream' => $stream,
            ]),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$status, &$sent, $conversation, $stream) {
                if (preg_match('#^HTTP/\\S+\\s+(\d{3})#i', $header, $m)) {
                    $status = (int)$m[1];
                }
                if ($stream && $status >= 200 && $status < 300 && !$sent) {
                    header('Content-Type: text/event-stream; charset=utf-8');
                    header('Cache-Control: no-cache');
                    header('X-Accel-Buffering: no');
                    if ($conversation !== null) {
                        header('X-Conversation-Uuid: ' . $conversation->uuid);
                    }
                    $sent = true;
                }
                return strlen($header);
            },
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$sent, &$errorBody, &$sseBody) {
                if ($sent) {
                    $sseBody .= $data;
                    echo $data;
                    flush();
                } else {
                    $errorBody .= $data;
                }
                return strlen($data);
            },
        ]);
        $ok = curl_exec($ch);
        curl_close($ch);
        return $ok !== false;
    }
    /**
     * Reconstruct the full assistant text from a buffered SSE stream.
     */
    private function extractStreamedText(string $sse): string
    {
        $text = '';
        foreach (preg_split('/\r?\n\r?\n/', $sse) as $event) {
            if (!str_starts_with($event, 'data:')) {
                continue;
            }
            $data = trim(substr($event, 5));
            if ($data === '[DONE]') {
                continue;
            }
            $chunk = json_decode($data, true);
            $delta = $chunk['choices'][0]['delta']['content'] ?? null;
            if (is_string($delta)) {
                $text .= $delta;
            }
        }
        return $text;
    }
}
