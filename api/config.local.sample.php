<?php
/**
 * Copy this file to config.local.php (gitignored) and fill in your key.
 *
 *   cp config.local.sample.php config.local.php
 */

return [
    'params' => [
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
