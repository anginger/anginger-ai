<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\controllers;

use Yii;
use yii\web\Response;

/**
 * Server-rendered pages: login, logout, error. `/` just forwards to the chat.
 */
class SiteController extends BaseController
{
    public $layout = '@app/views/layouts/main';

        /**
     * Unauthenticated health check for Docker / Render.
     */
    public function actionHealth(): Response
    {
        try {
            Yii::$app->db->createCommand('SELECT 1')->queryScalar();
        } catch (\Throwable $e) {
            Yii::$app->response->setStatusCode(503);
            return $this->asJson(['status' => 'error']);
        }
        return $this->asJson(['status' => 'ok']);
    }

public function actionIndex(): Response
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }
        return $this->redirect('/chat');
    }

    public function actionLogin(): string|Response
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect('/chat');
        }
        $this->layout = '@app/views/layouts/blank';
        return $this->render('login');
    }

    public function actionLogout(): Response
    {
        $identity = Yii::$app->user->identity;
        if ($identity instanceof User) {
            Yii::$app->params['history_user'] = $identity->uuid; // keep attribution after logout
        }
        Yii::$app->user->logout();
        return $this->redirect(['site/login']);
    }

    public function actionError(): string
    {
        $this->layout = '@app/views/layouts/blank';
        $exception = Yii::$app->errorHandler->exception;
        if ($exception !== null && method_exists($exception, 'getStatusCode')) {
            Yii::$app->response->setStatusCode($exception->getStatusCode());
        }
        return $this->render('error', ['exception' => $exception ?? new \Exception('unknown error')]);
    }
}
