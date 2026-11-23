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
        <h1>人員管理</h1>
        <button class="btn primary" @click="openCreate()">+ 新增人員</button>
    </div>
    <div class="card">
        <table>
            <thead>
            <tr><th>帳號</th><th>姓名</th><th>角色</th><th>建立時間</th><th></th></tr>
            </thead>
            <tbody>
            <template x-for="u in items" :key="u.uuid">
                <tr>
                    <td x-text="u.username"></td>
                    <td x-text="u.display_name"></td>
                    <td><span class="badge" x-text="u.role"></span></td>
                    <td x-text="ts(u.created_time)"></td>
                    <td>
                        <button class="btn" @click="openEdit(u)">編輯</button>
                        <button class="btn danger" @click="remove(u)">刪除</button>
                    </td>
                </tr>
            </template>
            <tr x-show="items.length === 0"><td colspan="5" class="empty">尚無資料</td></tr>
            </tbody>
        </table>
    </div>

    <div class="modal" x-show="mode !== 0" @click.self="mode = 0">
        <div class="card modal-card">
            <h2 x-text="mode === 1 ? '新增人員' : '編輯人員'"></h2>
            <p class="error" x-show="message" x-text="message"></p>
            <label>帳號<input x-model="form.username"></label>
            <label>密碼（留空表示不變）<input type="password" x-model="form.password"></label>
            <label>姓名<input x-model="form.display_name"></label>
            <label>角色
                <select x-model="form.role">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                </select>
            </label>
            <div class="row">
                <button class="btn" @click="mode = 0">取消</button>
                <button class="btn primary" @click="submit()" x-text="mode === 1 ? '送出' : '儲存'"></button>
            </div>
        </div>
    </div>
</div>
<script>
    window.__ITEMS__ = <?= json_encode($items) ?>;
</script>
