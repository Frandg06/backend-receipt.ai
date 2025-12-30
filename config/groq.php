<?php

return [
    'api_key' => env('GROQ_API_KEY', ''),
    'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
    'model' => env('GROQ_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct'),
    'timeout' => (int) env('GROQ_TIMEOUT', 60),
    'temperature' => (float) env('GROQ_TEMPERATURE', 0.1),
    'max_retries' => (int) env('GROQ_MAX_RETRIES', 2),
];
