<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

use anginger\assets\AppAsset;
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
$path = Yii::$app->request->pathInfo;
$this->beginPage();
?><!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title ?: 'Anginger AI') ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<header class="ts-app-topbar">
    <div class="start">
        <div class="item is-text"><strong>Anginger AI</strong></div>
    </div>
    <div class="center">
        <nav class="ts-menu">
            <a class="item<?= $path === 'chat' ? ' is-active' : '' ?>" href="/chat">Chat</a>
            <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->role === 'admin'): ?>
                <a class="item<?= $path === 'user' ? ' is-active' : '' ?>" href="/user">人員管理</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="end">
        <?php if (!Yii::$app->user->isGuest): ?>
            <div class="item is-text"><?= Html::encode(Yii::$app->user->identity->display_name) ?></div>
            <a class="item" href="/site/logout">登出</a>
        <?php endif; ?>
    </div>
</header>
<main class="ts-container app-main">
    <?= $content ?>
</main>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
