<?php

/**
 * Gemini AI configuration file.
 */

return [
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    'timeout' => (int) env('GEMINI_TIMEOUT', 30),
];
