<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

$config = [
    'id' => 'anginger-ai',
    'basePath' => __DIR__,
    'controllerNamespace' => 'anginger\\controllers',
    'aliases' => [
        '@anginger' => __DIR__,
    ],
    'components' => [
        'db' => [
            'class' => 'yii\\db\\Connection',
            // To use MySQL instead of SQLite, change the DSN to e.g.
            // 'mysql:host=127.0.0.1;dbname=anginger_ai' and add username/password.
            'dsn' => 'sqlite:@anginger/database.sqlite',
        ],
        'request' => [
            // required for the session cookie
            'cookieValidationKey' => 'anginger-ai-dev-key-change-me-in-production',
            // same-origin fetch() from the Alpine.js frontend
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => 'yii\\web\\JsonParser',
            ],
        ],
        // session-based authentication; pages are server-rendered HTML,
        // API controllers return JSON/SSE explicitly
        'user' => [
            'identityClass' => 'anginger\\models\\User',
            'enableSession' => true,
            'enableAutoLogin' => false,
            'loginUrl' => null, // API returns 401/403 JSON instead of redirecting
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // pages
                'login' => 'site/login',
                'logout' => 'site/logout',
                // every business domain is a module with its own page + JSON API
                '<module:chat|user>' => '<module>/page/index',
                // JSON/SSE API consumed by the Alpine.js frontend (fetch)
                'api/authentic/<action:\w+>' => 'authentic/<action>',
                'api/<module:chat|user>' => '<module>/api/index',
                'api/<module:chat|user>/<action:\w+>' => '<module>/api/<action>',
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
    ],
    // application-level modules (NOT a component): each business domain
    // owns its page route and its /api/* API
    'modules' => [
        'chat' => 'anginger\\modules\\chat\\Module',
        'user' => 'anginger\\modules\\user\\Module',
    ],
    'params' => [
        // OpenAI settings. The key is read from the OPENAI_API_KEY env var;
        // you can also override any of these in config.local.php (gitignored):
        //   'params' => ['openai' => ['key' => 'sk-...', 'model' => 'gpt-4o', ...]]
        'openai' => [
            'key' => getenv('OPENAI_API_KEY') ?: null,
            'model' => getenv('OPENAI_MODEL') ?: 'gpt-4o-mini',
            'baseUrl' => rtrim(getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1', '/'),
        ],
    ],
];

// local overrides (secrets) — copy config.local.sample.php to config.local.php
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    $config = array_replace_recursive($config, require $localConfig);
}

return $config;
