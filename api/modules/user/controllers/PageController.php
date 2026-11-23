<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\modules\user\controllers;

use anginger\controllers\PageBaseController;
use anginger\models\User;
use Yii;

class PageController extends PageBaseController
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        // personnel management is for admins; everyone else goes back to chat
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof User || $identity->role !== 'admin') {
            Yii::$app->response->redirect('/chat');
            return false;
        }
        return true;
    }

    public function actionIndex(): string
    {
        $items = User::find()->orderBy(['username' => SORT_ASC])->all();
        return $this->render('index', ['items' => $items]);
    }
}
