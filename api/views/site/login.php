<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

/* @var $this \yii\web\View */
$this->title = '登入 - Anginger AI';
?>
<main class="ts-app-center">
    <div class="ts-box login-wrap" x-data="loginForm()">
        <h1>Anginger AI</h1>
        <p class="muted">自架版 ChatGPT — 請登入繼續</p>
        <p class="error" x-show="message" x-text="message"></p>
        <label class="app-field">帳號
            <span class="ts-input"><input x-model="username" @keyup.enter="submit()" autofocus></span>
        </label>
        <label class="app-field">密碼
            <span class="ts-input"><input type="password" x-model="password" @keyup.enter="submit()"></span>
        </label>
        <button class="ts-button is-fluid" @click="submit()" :disabled="loading">登入</button>
    </div>
</main>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('loginForm', () => ({
        username: '',
        password: '',
        message: '',
        loading: false,
        async submit() {
            this.loading = true;
            this.message = '';
            try {
                const r = await fetch('/api/authentic/session', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({username: this.username, password: this.password})
                });
                if (r.status === 201) {
                    location.href = '/chat';
                    return;
                }
                const d = await r.json().catch(() => ({}));
                this.message = d.message || '登入失敗';
            } catch (e) {
                this.message = '無法連線到伺服器';
            }
            this.loading = false;
        }
    }));
});
</script>
