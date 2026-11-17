<?php

// La integración queda deshabilitada hasta configurar una clave y un modelo compatibles.
return [
    'provider' => env('AI_PROVIDER', 'openai'),
    'enabled' => env('OPENAI_ENABLED', false),
    'key' => env('OPENAI_API_KEY', ''),
    'model' => env('OPENAI_MODEL', ''),
    'timeout' => (int) env('OPENAI_TIMEOUT', 15),
    'max_output_tokens' => (int) env('OPENAI_MAX_OUTPUT_TOKENS', 300),
    'requests_per_minute' => (int) env('OPENAI_REQUESTS_PER_MINUTE', 5),
    'requests_per_day' => (int) env('OPENAI_REQUESTS_PER_DAY', 50),
    'attempts_per_incident' => (int) env('OPENAI_ATTEMPTS_PER_INCIDENT', 3),
];
