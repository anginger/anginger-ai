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
    <aside class="sidebar card">
        <button class="btn primary" style="width:100%" @click="newConversation()">+ 新對話</button>
        <div class="conv-list">
            <template x-for="c in conversations" :key="c.uuid">
                <div class="conv-item" :class="{active: current && current.uuid === c.uuid}" @click="openConversation(c.uuid)">
                    <div class="conv-title" x-text="c.title"></div>
                    <div class="conv-meta">
                        <span x-text="c.message_count + ' 訊息'"></span>
                        <button class="btn danger mini" @click.stop="removeConversation(c)">刪</button>
                    </div>
                </div>
            </template>
            <div class="empty" x-show="conversations.length === 0">尚無對話</div>
        </div>
    </aside>

    <section class="chat-main">
        <div class="chat-head card">
            <h1 x-text="current ? current.title : 'Anginger AI'"></h1>
            <span class="badge" x-text="model || '...'"></span>
            <button class="btn" x-show="current" @click="openSettings()">系統提示詞</button>
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
    </section>

    <div class="modal" x-show="settingsOpen" @click.self="settingsOpen = false">
        <div class="card modal-card">
            <h2>對話設定</h2>
            <p class="error" x-show="settingsMessage" x-text="settingsMessage"></p>
            <label>標題<input x-model="settingsForm.title"></label>
            <label>系統提示詞（system prompt）
                <textarea x-model="settingsForm.system_prompt" rows="6" placeholder="例如：你是一位資深 PHP 工程師，回答要簡潔並給程式碼範例"></textarea>
            </label>
            <div class="row">
                <button class="btn" @click="settingsOpen = false">取消</button>
                <button class="btn primary" @click="saveSettings()">儲存</button>
            </div>
        </div>
    </div>
</div>
<script>
    window.__MODEL__ = <?= json_encode($model) ?>;
</script>
