<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\modules\chat\controllers;

use anginger\controllers\PageBaseController;
use Yii;

class PageController extends PageBaseController
{
    /** Chat is available to every logged-in user. */
    public bool $requireAdmin = false;

    public function actionIndex(): string
    {
        return $this->render('index', [
            'model' => Yii::$app->params['openai']['model'] ?? '',
        ]);
    }
}
