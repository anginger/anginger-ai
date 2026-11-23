<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

use anginger\modules\chat\assets\ChatAsset;

/* @var $this \yii\web\View */
/* @var $model string */

$this->title = 'Chat - Anginger AI';
ChatAsset::register($this);
?>
<div class="chat" x-data="chatPage()" x-init="init()">
    <div class="chat-head card">
        <h1>Anginger AI</h1>
        <span class="badge" x-text="model || '...'"></span>
        <button class="btn" @click="clear()" x-show="messages.length">清除對話</button>
    </div>

    <div class="card chat-log" x-ref="log">
        <template x-for="(m, i) in messages" :key="i">
            <div class="msg" :class="m.role">
                <div class="bubble" x-text="m.content"></div>
            </div>
        </template>
        <div class="msg assistant" x-show="loading">
            <div class="bubble typing"><span></span><span></span><span></span></div>
        </div>
        <div class="empty" x-show="messages.length === 0 && !loading">問點什麼吧 👋</div>
    </div>

    <p class="error" x-show="error" x-text="error"></p>

    <div class="composer card">
        <textarea x-model="input" rows="2" placeholder="輸入訊息，Enter 送出..."
                  @keydown.enter.prevent="send()" :disabled="loading"></textarea>
        <button class="btn primary" @click="send()" :disabled="loading || !input.trim()">送出</button>
    </div>
</div>
<script>
    window.__MODEL__ = <?= json_encode($model) ?>;
</script>
