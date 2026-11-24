<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

/* @var $this \yii\web\View */
$this->title = '登入 - Anginger AI';
?>
<div class="login-split">
    <aside class="login-hero">
        <div class="brand"><span class="brand-mark">A</span><span class="brand-name">Anginger AI</span></div>
        <h1>自架 AI 工作台，<br>金鑰不出伺服器。</h1>
        <p class="hero-sub">以 OpenAI 相容 API 在伺服器端代理對話，對話內容、上下文與系統提示詞都掌握在自己手上。</p>
        <ul class="hero-feats">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>多對話與上下文管理</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3"/><path d="M1 14h6M9 8h6M17 16h6"/></svg>每個對話可設系統提示詞</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="4.5"/><path d="M10.7 12.3 21 2M15 8l3 3M18 5l2.5 2.5"/></svg>API 金鑰只留在伺服器</li>
        </ul>
        <p class="hero-foot">Anginger 工作台 · AI</p>
    </aside>
    <div class="login-panel">
        <form class="login-card" x-data="loginForm()" @submit.prevent="submit()">
            <h2>登入</h2>
            <p class="muted">歡迎回來，請輸入帳號密碼。</p>
            <p class="error" aria-live="polite" x-show="message" x-text="message"></p>
            <label class="app-field">帳號
                <span class="ts-input"><input name="username" autocomplete="username" spellcheck="false" x-model="username" autofocus></span>
            </label>
            <label class="app-field">密碼
                <span class="ts-input"><input type="password" name="password" autocomplete="current-password" x-model="password"></span>
            </label>
            <button class="ts-button is-fluid" type="submit" :disabled="loading" x-text="loading ? '登入中…' : '登入'"></button>
            <p class="muted ldap-hint" x-show="ldap" x-cloak>已啟用 LDAP/AD 登入 — 內建帳號與目錄帳號皆可登入</p>
        </form>
    </div>
</div>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('loginForm', () => ({
        username: '',
        password: '',
        message: '',
        loading: false,
        ldap: false,
        async init() {
            try {
                const r = await fetch('/api/authentic/ldap', {credentials: 'same-origin'});
                if (r.ok) { const d = await r.json(); this.ldap = !!d.enabled; }
            } catch (e) { /* hint is optional */ }
        },
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
