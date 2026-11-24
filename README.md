# Anginger AI

Anginger AI is a ChatGPT-style chat app built with the same architecture as
[`anginger-erp`](https://github.com/anginger/anginger-erp) /
[`anginger-hr`](https://github.com/anginger/anginger-hr):
**Yii 2** backend + server-rendered PHP pages + Alpine.js (vendored, no build
step), one module per domain. The chat module proxies the
**OpenAI Chat Completions API** — the API key stays on the server, the browser
only talks to `/api/chat` and receives a streamed (SSE) reply.

## Stack

| Layer  | Technology                                                       |
| ------ | ---------------------------------------------------------------- |
| App    | PHP 8 + [Yii 2](https://www.yiiframework.com/) + SQLite          |
| UI     | Server-rendered PHP views + Alpine.js + plain CSS (no build step) |
| AI     | OpenAI Chat Completions (`POST {baseUrl}/chat/completions`, SSE) |
| Deploy | Docker (nginx + php-fpm + supervisord, single container, port 8888) |

## Features

- **Conversation management**: conversations and messages are persisted
  (`conversations` / `messages` tables); the sidebar lists them, you can
  reopen, rename or delete them, and every turn sends the full stored
  context back to the model (server-side, survives reloads)
- **System prompt per conversation**: editable in the chat UI, prepended as
  the `system` message on every request
- **Personnel management** (`/user`, admin role only): create/edit/delete
  login accounts, assign `admin`/`user` roles; users only see their own
  conversations
- Replies stream token-by-token (SSE); the API key stays server-side

## Structure

```
api/
├── config.php / console.php     web/console config (console.php runs migrate)
├── config.local.sample.php      copy to config.local.php (gitignored) for your key
├── controllers/                 BaseController (CORS + audit log), PageBaseController,
│                                AuthenticController (session login), SiteController
├── models/                      User (IdentityInterface), History
├── modules/chat/                /chat page (Alpine.js) + /api/chat (SSE proxy to OpenAI)
├── views/                       layouts, site (login/error)
├── web/                         index.php entry + assets (alpine.min.js, app.css, module-chat.js)
└── migrations/                  users (seeded admin/admin) + histories (audit log)
```

## OpenAI key

Set the key via environment variable (recommended):

```sh
export OPENAI_API_KEY=sk-...
export OPENAI_MODEL=gpt-4o-mini        # optional, default
echo "OPENAI_BASE_URL=https://api.openai.com/v1"  # only for Azure/proxies/mocks
```

or via `api/config.local.php` (gitignored — see `config.local.sample.php`):

```php
return [
    'params' => [
        'openai' => [
            'key' => 'sk-...',
            'model' => 'gpt-4o-mini',
            'baseUrl' => 'https://api.openai.com/v1',
        ],
    ],
];
```

## Default User

| Username | Password |
| -------- | -------- |
| admin    | admin    |

## LDAP / Active Directory 登入

設定 `LDAP_ENABLED=true`（環境變數或 `api/config.local.php`，見 `config.local.sample.php`）後：

- 登入會先以本機密碼驗證**內建帳號**（內建帳號不受影響，仍可登入使用），失敗再向 LDAP/AD 目錄驗證
- 目錄驗證成功的使用者會**自動擴充進內建 `users` 表**（`auth_source=ldap`，同步 `ldap_dn`、顯示名稱、email）
- 啟用期間**停用內建註冊**（無法手動新增帳號），帳號統一由目錄同步
- 人員管理頁可**封存/解除封存**：封存後該帳號無法以密碼或 LDAP 登入，現有 session 立即失效

環境變數一覽（AD 範例）：`LDAP_HOST`、`LDAP_PORT`（636=LDAPS）、`LDAP_BASE_DN`、
`LDAP_BIND_DN`/`LDAP_BIND_PASSWORD`（服務帳號搜尋）或 `LDAP_USER_DN_PATTERN`（直接 bind）、
`LDAP_USER_FILTER`（AD: `(sAMAccountName=%s)`）、`LDAP_USERNAME_ATTRIBUTE`（AD: `sAMAccountName`）、
`LDAP_STARTTLS`、`LDAP_TIMEOUT`。需要 PHP **ext-ldap**（Docker image 已內建 `php8-ldap`）。

## Development

```sh
export OPENAI_API_KEY=sk-...
./bootstrap.dev.sh
```

Then open <http://127.0.0.1:1270/login> (admin / admin) and chat at `/chat`.

Manual steps:

```sh
cd api
composer install
php console.php migrate --interactive=0
cd web && php -S 127.0.0.1:1270 index.php
```

## API

| Method | Path                     | Description                                             |
| ------ | ------------------------ | ------------------------------------------------------- |
| GET    | `/api/authentic/session` | current user (200/401)                                   |
| POST   | `/api/authentic/session` | login (201/401)                                          |
| DELETE | `/api/authentic/session` | logout (204)                                             |
| GET    | `/api/chat`              | `{"model": "..."}` (200)                                 |
| POST   | `/api/chat`              | body `{"messages":[...]}` → SSE stream of the completion |

Unauthenticated API calls return `403 {"message":"forbidden"}`; pages redirect
to `/login`. Every request is logged into the `histories` table.

## Docker

```sh
docker build -t anginger-ai .
docker run -p 8888:8888 -e OPENAI_API_KEY=sk-... [-e OPENAI_MODEL=gpt-4o] anginger-ai
```

Open <http://localhost:8888/login>. The container runs
`php console.php migrate` on boot. nginx disables buffering for the SSE
stream (`fastcgi_buffering off`).

## License

[BSD 3-Clause License](LICENSE.md)
