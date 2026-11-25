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

開箱即用：**預設串接 ai-tech-tw 提供的免費 OpenAI 相容 gateway**（<https://github.com/ai-tech-tw/openai>）
— baseUrl `https://web-tech.tw/recv/openai/v1`、model `auto`、public trial key，不用申請。

要用自己的 key / endpoint，設環境變數即可覆蓋預設值：

```sh
export OPENAI_API_KEY=sk-...
export OPENAI_MODEL=gpt-4o-mini                          # optional
export OPENAI_BASE_URL=https://api.openai.com/v1         # only for Azure/proxies/mocks
export OPENAI_STREAM=false                               # 回應模式: true=串流SSE, false=非串流
```

或 via `api/config.local.php`（gitignored — see `config.local.sample.php`)。

回應模式（`stream`）：`true` 時以 SSE 串流逐字回傳（若 gateway 串流失敗會自動改非串流重試一次）；
`false` 時整段回覆一次回傳（後端仍以單一 SSE chunk 送給前端，UI 不變）。免費 gateway 的串流偶發 502，設 `OPENAI_STREAM=false` 最穩。

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
`LDAP_STARTTLS`、`LDAP_TIMEOUT`、`LDAP_DISPLAY_NAME_ATTRIBUTE`（預設 `cn`）、`LDAP_EMAIL_ATTRIBUTE`（預設 `mail`）。需要 PHP **ext-ldap**（Docker image 已內建 `php8-ldap`）。

## Deploy / Render

需要 **PHP >= 8.2**（`api/composer.json` 有寫，brick/math 依賴要求 ^8.2）。Render 上請用 **Docker service**（吃 repo 的 `render.yaml` + `Dockerfile`，PHP 8.4），或 native PHP service 時在 Dashboard 把 PHP version 選 8.2 以上。

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
| GET    | `/api/chat/conversations` | my conversations (200) |
| POST   | `/api/chat/conversations` | create conversation `{title, system_prompt}` (201) |
| GET/POST/PUT/DELETE | `/api/chat/conversation` | one conversation by `uuid` (read / rename+system prompt / delete) |
| GET    | `/api/user/all`          | user list, admin only (200/403) |
| POST   | `/api/user/archive`      | archive/unarchive `{uuid, archived}`, admin only (423 on archived login) |

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

## Security

- Session-based auth; all management pages/APIs are admin-only (403 otherwise). `User::fields()` never exposes the password hash or `ldap_dn`.
- CSRF is enabled globally: the layout emits a meta token and `app-ui.js` injects `X-CSRF-Token` into every same-origin mutating fetch (missing token -> 400).
- Passwords are bcrypt via `Yii::$app->security`; legacy sha256 hashes still verify and are transparently re-hashed on successful login.
- Login rate limit: 5 failed attempts per IP+username per 15 minutes -> 429. Archived accounts get 423 and their existing sessions are invalidated immediately.
- All view-embedded JSON goes through `Json::htmlEncode` (no `</script>` breakout); Alpine templates use `x-text`, never `x-html`.
- AI gateway calls force every upstream message to `role=user` (prompt-injection guard: the system prompt only comes from the server-side conversation settings), cap context at system + last 30 messages, and SSE parsing normalizes CRLF before splitting `data:` lines.
- SQLite runs in WAL mode with `foreign_keys=ON`; `GET /health` verifies the DB connection.

## Operational notes

- Error codes: `401` 未登入、`403` 已登入但無權限（管理功能限 admin）、`423` 帳號已封存、`429` 登入嘗試過於頻繁（5 次 / 15 分鐘，鎖定後回 429）。
- CSRF：所有會異動狀態的請求（POST/PUT/DELETE）需帶 `X-CSRF-Token` header（layout 已輸出 meta tag，`app-ui.js` 會自動為 fetch 注入），缺少時回 `400`。
- `GET /health` 不需登入，回 `{"status":"ok"}`，供 Docker `HEALTHCHECK` 與 Render `healthCheckPath` 使用。
- SQLite 為 WAL mode：備份請用 `sqlite3 api/database.sqlite ".backup backup.sqlite"`，或複製時一併複製 `database.sqlite-wal` / `-shm`（線上請先 `PRAGMA wal_checkpoint(TRUNCATE);`）。
- 測試：`cd api && vendor/bin/phpunit`（unit tests，自動用暫存 SQLite 跑 migrate）；E2E smoke：`BASE=http://127.0.0.1:1270 api/tests/e2e.sh`（需先啟動 dev server）。
- 正式環境請設定環境變數：`YII_ENV=prod`、`YII_DEBUG=false`、`COOKIE_VALIDATION_KEY`（隨機字串）、`DB_DSN` / `SQLITE_PATH`（預設 `api/database.sqlite`）。
## License

[BSD 3-Clause License](LICENSE.md)
