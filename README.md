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
