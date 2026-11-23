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
<main class="ts-app-center">
    <div class="ts-box login-wrap">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="error"><?= Html::encode($exception->getMessage()) ?></p>
        <p><a href="/">回到首頁</a></p>
    </div>
</main>
