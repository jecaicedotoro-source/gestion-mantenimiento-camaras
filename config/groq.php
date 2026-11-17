<?php

return [
    'enabled' => env('GROQ_ENABLED', false),
    'key' => env('GROQ_API_KEY', ''),
    'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
    'timeout' => (int) env('GROQ_TIMEOUT', 15),
    'max_completion_tokens' => (int) env('GROQ_MAX_COMPLETION_TOKENS', 600),
];
