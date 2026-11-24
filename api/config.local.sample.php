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
            // OpenAI API key (sk-...) — or set the OPENAI_API_KEY env var instead
            'key' => 'sk-...',
            // any chat model, e.g. gpt-4o-mini, gpt-4o, gpt-4.1
            'model' => 'gpt-4o-mini',
            // override only for Azure OpenAI / proxies / local mocks
            'baseUrl' => 'https://api.openai.com/v1',
        ],
    ],
];
