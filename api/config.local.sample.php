<?php
/**
 * Copy this file to config.local.php (gitignored) and fill in your key.
 *
 *   cp config.local.sample.php config.local.php
 */

return [
    'params' => [
        // LDAP / Active Directory login (or use the LDAP_* env vars instead)
        'ldap' => [
            'enabled' => false,
            'host' => 'ad.company.com',        // or 127.0.0.1 for OpenLDAP
            'port' => 389,                     // 636 for LDAPS
            'base_dn' => 'DC=company,DC=com',
            // service account used to search for users (leave empty to use
            // user_dn_pattern direct bind instead)
            'bind_dn' => 'CN=svc-anginger,OU=Service,DC=company,DC=com',
            'bind_password' => 'change-me',
            // Active Directory:  'user_filter' => '(sAMAccountName=%s)',
            //                     'username_attribute' => 'sAMAccountName',
            // OpenLDAP:           'user_filter' => '(uid=%s)',
            'user_filter' => '(sAMAccountName=%s)',
            'username_attribute' => 'sAMAccountName',
            'display_name_attribute' => 'displayName', // AD; OpenLDAP: cn
            'email_attribute' => 'mail',
            // or direct-bind strategy without a service account:
            // 'user_dn_pattern' => 'uid=%s,OU=users,DC=company,DC=com',
            'start_tls' => false,
            'timeout' => 5,
        ],
        'openai' => [
            // Defaults (in config.php) use the free ai-tech-tw gateway
            // (https://github.com/ai-tech-tw/openai): baseUrl
            // https://web-tech.tw/recv/openai/v1, model "auto", public trial key.
            // Set OPENAI_API_KEY / OPENAI_MODEL / OPENAI_BASE_URL env vars or
            // override here to use your own key, e.g.:
            // 'key' => 'sk-...',
            // 'model' => 'gpt-4o-mini',
            // 'baseUrl' => 'https://api.openai.com/v1',
            // response mode: true = SSE streaming (falls back to non-streaming
            // once if the gateway errors), false = always non-streaming
            'stream' => true,
        ],
    ],
];
