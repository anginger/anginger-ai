<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

use anginger\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
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
<header class="topbar">
    <div class="brand">Anginger AI</div>
    <nav class="nav">
        <a href="<?= Url::to(['/chat']) ?>">Chat</a>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->role === 'admin'): ?>
            <a href="<?= Url::to(['/user']) ?>">人員管理</a>
        <?php endif; ?>
    </nav>
    <div class="user">
        <?php if (!Yii::$app->user->isGuest): ?>
            <span><?= Html::encode(Yii::$app->user->identity->display_name) ?></span>
            <a href="<?= Url::to(['/site/logout']) ?>">登出</a>
        <?php endif; ?>
    </div>
</header>
<main class="container">
    <?= $content ?>
</main>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
