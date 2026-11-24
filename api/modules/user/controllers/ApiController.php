<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\modules\user\controllers;

use anginger\components\LdapClient;
use anginger\controllers\ResourceController;
use anginger\models\User;
use Yii;
use yii\db\ActiveRecord;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Personnel management API — admin role only.
 *
 *   GET    /api/user?uuid=...   POST /api/user   PUT /api/user   DELETE /api/user?uuid=...
 *   GET    /api/user/all
 *   POST   /api/user/archive    body {uuid, archived} — block/unblock an account
 *
 * While LDAP/AD is enabled, built-in registration (POST create) is disabled —
 * accounts are provisioned from the directory on first login instead.
 */
class ApiController extends ResourceController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'archive' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * POST /api/user/archive — archive (block) or unarchive an account.
     * Archived users cannot log in (local password or LDAP) and lose live sessions.
     */
    public function actionArchive(): Response
    {
        if (!$this->requireLogin()) {
            return $this->forbidden();
        }
        $form = Yii::$app->request->getBodyParams();
        $uuid = $form['uuid'] ?? null;
        if ($uuid === null || $uuid === '' || !isset($form['archived'])) {
            return $this->badRequest();
        }
        $user = User::findOne(strval($uuid));
        if ($user === null) {
            return $this->notFound();
        }
        $user->archived = $form['archived'] ? 1 : 0;
        if (!$user->save(false, ['archived'])) {
            return $this->serverError();
        }
        return $this->asJson(['uuid' => $user->uuid, 'archived' => $user->archived]);
    }

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof User || $identity->role !== 'admin') {
            $response = Yii::$app->response;
            $response->setStatusCode(403);
            $response->format = Response::FORMAT_JSON;
            $response->data = ['message' => 'forbidden'];
            return false;
        }
        return true;
    }

    protected function modelClass(): string
    {
        return User::class;
    }

    protected function pkParam(): string
    {
        return 'uuid';
    }

    protected function requiredCreateFields(): array
    {
        return ['username', 'password', 'display_name', 'role'];
    }

    protected function requiredUpdateFields(): array
    {
        // password is optional on update: when empty the old hash is kept
        return ['uuid', 'username', 'display_name', 'role'];
    }

    protected function validateCreate(array $form): ?Response
    {
        // LDAP/AD enabled: built-in registration is off, accounts come from the directory
        if (LdapClient::enabled()) {
            Yii::$app->response->setStatusCode(403);
            return $this->asJson(['message' => 'LDAP/AD 登入已啟用，無法手動新增內建帳號（帳號由 LDAP 同步）']);
        }
        if (!in_array($form['role'] ?? null, ['admin', 'user'], true)) {
            return $this->badRequest();
        }
        return null;
    }

    protected function validateUpdate(ActiveRecord $model, array $form): ?Response
    {
        return $this->validateCreate($form);
    }

    protected function beforeDelete(ActiveRecord $model): ?Response
    {
        // nobody deletes their own account while using it
        if ($model->uuid === Yii::$app->user->identity->uuid) {
            return $this->badRequest();
        }
        return null;
    }

    protected function fillCreate(array $form): ActiveRecord
    {
        $user = new User();
        $user->uuid = self::uuidV7();
        $user->username = strval($form['username']);
        $user->password = hash('sha256', strval($form['password']));
        $user->display_name = strval($form['display_name']);
        $user->role = strval($form['role']);
        $user->created_time = time();
        return $user;
    }

    protected function fillUpdate(ActiveRecord $model, array $form): void
    {
        /** @var User $model */
        $model->username = strval($form['username']);
        $model->display_name = strval($form['display_name']);
        $model->role = strval($form['role']);
        if (!empty($form['password'])) {
            $model->password = hash('sha256', strval($form['password']));
        }
    }

    public function actionAll(): Response
    {
        if (!$this->requireLogin()) {
            return $this->forbidden();
        }
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof User || $identity->role !== 'admin') {
            return $this->forbidden();
        }
        // User::fields() hides the password hash
        return $this->asJson(User::find()->orderBy(['username' => SORT_ASC])->all());
    }
}
