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
        // LDAP / Active Directory login. Every value can be set via an
        // environment variable (LDAP_*) or overridden in config.local.php
        // (gitignored) — copy config.local.sample.php to get started.
        //
        // When enabled: built-in registration is disabled, LDAP users are
        // provisioned into `users` on first login, and built-in (local)
        // accounts keep working with their local password.
        'ldap' => [
            'enabled' => filter_var(getenv('LDAP_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN),
            'host' => getenv('LDAP_HOST') ?: '127.0.0.1',
            'port' => (int)(getenv('LDAP_PORT') ?: 389),          // 636 for LDAPS
            'base_dn' => getenv('LDAP_BASE_DN') ?: '',            // e.g. DC=company,DC=com
            'bind_dn' => getenv('LDAP_BIND_DN') ?: '',            // service account (optional)
            'bind_password' => getenv('LDAP_BIND_PASSWORD') ?: '',
            // strategy 1 (service account search): user_filter is sprintf'd with
            // the escaped username — AD: (sAMAccountName=%s), OpenLDAP: (uid=%s)
            'user_filter' => getenv('LDAP_USER_FILTER') ?: '(uid=%s)',
            // strategy 2 (direct bind, no service account): DN pattern, %s = escaped username
            'user_dn_pattern' => getenv('LDAP_USER_DN_PATTERN') ?: '',
            'username_attribute' => getenv('LDAP_USERNAME_ATTRIBUTE') ?: 'uid',   // AD: sAMAccountName
            'display_name_attribute' => getenv('LDAP_DISPLAY_NAME_ATTRIBUTE') ?: 'cn',
            'email_attribute' => getenv('LDAP_EMAIL_ATTRIBUTE') ?: 'mail',
            'start_tls' => filter_var(getenv('LDAP_STARTTLS') ?: 'false', FILTER_VALIDATE_BOOLEAN),
            'timeout' => (int)(getenv('LDAP_TIMEOUT') ?: 5),
        ],
        // OpenAI-compatible settings. Defaults point at the free ai-tech-tw
        // gateway (https://github.com/ai-tech-tw/openai) so the app works out
        // of the box; set OPENAI_API_KEY / OPENAI_MODEL / OPENAI_BASE_URL env
        // vars (or config.local.php) to use your own key/endpoint instead:
        //   'params' => ['openai' => ['key' => 'sk-...', 'model' => 'gpt-4o', ...]]
        'openai' => [
            'key' => getenv('OPENAI_API_KEY') ?: 'nymph zr3Pjc68z4bOtw==', // ai-tech-tw public trial key
            'model' => getenv('OPENAI_MODEL') ?: 'auto',
            'baseUrl' => rtrim(getenv('OPENAI_BASE_URL') ?: 'https://web-tech.tw/recv/openai/v1', '/'),
            // response mode: true = SSE streaming, false = single non-streaming
            // request (the reply is still delivered to the UI as one SSE chunk)
            'stream' => filter_var(getenv('OPENAI_STREAM') ?: 'true', FILTER_VALIDATE_BOOLEAN),
        ],
    ],
];

// local overrides (secrets) — copy config.local.sample.php to config.local.php
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    $config = array_replace_recursive($config, require $localConfig);
}

return $config;
