<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $exception \Throwable */
$this->title = '錯誤 - Anginger AI';
?>
<div class="login-panel" style="min-height:100vh">
    <div class="login-card">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php $debug = defined('YII_DEBUG') && YII_DEBUG; ?>
        <p class="error"><?= ($debug || $exception instanceof \yii\web\HttpException) ? Html::encode($exception->getMessage()) : '系統發生錯誤，請稍後再試。' ?></p>
        <p><a href="/chat">回到首頁</a></p>
    </div>
</div>
