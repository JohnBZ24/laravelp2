<?php

return [
    'default_provider' => env('AI_PROVIDER', 'openai'),
    'default_model' => env('AI_MODEL', 'gpt-4.1-mini'),
    'request_timeout_seconds' => (int) env('AI_REQUEST_TIMEOUT_SECONDS', 30),

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'chat_endpoint' => env('OPENAI_CHAT_ENDPOINT', '/chat/completions'),
        ],
    ],

    'chat' => [
        'max_messages_per_minute' => (int) env('AI_CHAT_MAX_MESSAGES_PER_MINUTE', 20),
        'monthly_token_quota' => (int) env('AI_MONTHLY_TOKEN_QUOTA', 250000),
        'max_tokens' => (int) env('AI_CHAT_MAX_TOKENS', 512),
        'temperature' => (float) env('AI_CHAT_TEMPERATURE', 0.7),
    ],
];
