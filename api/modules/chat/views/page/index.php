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
<div class="chat-layout" x-data="chatPage()" x-init="init()">
    <aside class="ts-box chat-sidebar">
        <button class="ts-button is-fluid" @click="newConversation()">+ 新對話</button>
        <div class="conv-list">
            <template x-for="c in conversations" :key="c.uuid">
                <a class="conv-item" tabindex="0" role="button" :class="{ 'is-active': current && current.uuid === c.uuid }" @click.prevent="openConversation(c.uuid)">
                    <div class="conv-title" x-text="c.title"></div>
                    <div class="conv-meta">
                        <span x-text="c.message_count + ' 訊息'"></span>
                        <button class="ts-button is-small is-negative is-outlined" @click.stop.prevent="removeConversation(c)">刪除</button>
                    </div>
                </a>
            </template>
            <div class="empty" x-show="conversations.length === 0">尚無對話</div>
        </div>
    </aside>

    <section class="chat-main">
        <div class="ts-box page-head">
            <h1 x-text="current ? current.title : 'Anginger AI'" style="margin:0"></h1>
            <span class="ts-chip" x-text="model || '…'"></span>
            <button class="ts-button is-outlined is-small" x-show="current" @click="openSettings()">系統提示詞</button>
        </div>

        <div class="ts-box chat-log" x-ref="log">
            <template x-for="(m, i) in messages" :key="i">
                <div class="ts-conversation" :class="{ 'is-sent': m.role === 'user' }">
                    <div class="bubble" x-text="m.content"></div>
                </div>
            </template>
            <div class="ts-conversation" x-show="loading">
                <div class="bubble typing"><span></span><span></span><span></span></div>
            </div>
            <div class="empty" x-show="messages.length === 0 && !loading">問點什麼吧 👋</div>
        </div>

        <p class="error" aria-live="polite" x-show="error" x-text="error"></p>

        <div class="ts-box composer">
            <span class="ts-input"><textarea name="input" autocomplete="off" x-model="input" rows="2" placeholder="輸入訊息，Enter 送出…"
                  @keydown.enter.prevent="send()" :disabled="loading"></textarea></span>
            <button class="ts-button" @click="send()" :disabled="loading || !input.trim()">送出</button>
        </div>
    </section>

    <dialog class="ts-modal" :open="settingsOpen" @click.self="settingsOpen = false">
        <form class="content" @submit.prevent="saveSettings()">
            <h2>對話設定</h2>
            <p class="error" aria-live="polite" x-show="settingsMessage" x-text="settingsMessage"></p>
            <label class="app-field">標題
                <span class="ts-input"><input name="title" autocomplete="off" x-model="settingsForm.title"></span>
            </label>
            <label class="app-field">系統提示詞（system prompt）
                <span class="ts-input"><textarea name="system_prompt" autocomplete="off" x-model="settingsForm.system_prompt" rows="6" placeholder="例如：你是一位資深 PHP 工程師，回答要簡潔並給程式碼範例"></textarea></span>
            </label>
            <div class="ts-buttons is-end-aligned">
                <button class="ts-button is-outlined" type="button" @click="settingsOpen = false">取消</button>
                <button class="ts-button" type="submit">儲存</button>
            </div>
        </form>
    </dialog>
</div>
<script>
    window.__MODEL__ = <?= \yii\helpers\Json::htmlEncode($model) ?>;
</script>
