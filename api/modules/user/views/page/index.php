<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

use anginger\modules\user\assets\UserAsset;

/* @var $this \yii\web\View */
/* @var $items \anginger\models\User[] */

$this->title = '人員管理 - Anginger AI';
UserAsset::register($this);
?>
<div x-data="userPage()">
    <div class="page-head">
        <h1 style="margin:0">人員管理</h1>
        <button class="ts-button" @click="openCreate()">+ 新增人員</button>
    </div>
    <div class="ts-box is-flush">
        <table class="ts-table">
            <thead>
            <tr><th>帳號</th><th>姓名</th><th>角色</th><th>建立時間</th><th></th></tr>
            </thead>
            <tbody>
            <template x-for="u in items" :key="u.uuid">
                <tr>
                    <td x-text="u.username"></td>
                    <td x-text="u.display_name"></td>
                    <td><span class="ts-chip" x-text="u.role"></span></td>
                    <td x-text="ts(u.created_time)"></td>
                    <td>
                        <button class="ts-button is-small is-outlined" @click="openEdit(u)">編輯</button>
                        <button class="ts-button is-small is-negative is-outlined" @click="remove(u)">刪除</button>
                    </td>
                </tr>
            </template>
            <tr x-show="items.length === 0"><td colspan="5" class="empty">尚無資料</td></tr>
            </tbody>
        </table>
    </div>

    <dialog class="ts-modal" :open="mode !== 0" @click.self="mode = 0">
        <form class="content" @submit.prevent="submit()">
            <h2 x-text="mode === 1 ? '新增人員' : '編輯人員'"></h2>
            <p class="error" aria-live="polite" x-show="message" x-text="message"></p>
            <label class="app-field">帳號
                <span class="ts-input"><input name="username" autocomplete="off" spellcheck="false" x-model="form.username"></span>
            </label>
            <label class="app-field">密碼（留空表示不變）
                <span class="ts-input"><input type="password" name="password" autocomplete="new-password" x-model="form.password"></span>
            </label>
            <label class="app-field">姓名
                <span class="ts-input"><input name="display_name" autocomplete="off" x-model="form.display_name"></span>
            </label>
            <label class="app-field">角色
                <span class="ts-input"><select name="role" x-model="form.role">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                </select></span>
            </label>
            <div class="ts-buttons is-end-aligned">
                <button class="ts-button is-outlined" type="button" @click="mode = 0">取消</button>
                <button class="ts-button" type="submit" x-text="mode === 1 ? '送出' : '儲存'"></button>
            </div>
        </form>
    </dialog>
</div>
<script>
    window.__ITEMS__ = <?= json_encode(\yii\helpers\ArrayHelper::toArray($items)) ?>;
</script>
