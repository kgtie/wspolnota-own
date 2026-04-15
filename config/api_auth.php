<?php

return [
    'access_ttl_seconds' => (int) env('API_ACCESS_TTL_SECONDS', 900),
    'refresh_ttl_days' => (int) env('API_REFRESH_TTL_DAYS', 30),
    'email_verification_ttl_minutes' => (int) env('API_EMAIL_VERIFICATION_TTL_MINUTES', 60),
    'mobile_email_verification_url' => env('API_MOBILE_EMAIL_VERIFICATION_URL'),
    'mobile_password_reset_url' => env('API_MOBILE_PASSWORD_RESET_URL'),
];
