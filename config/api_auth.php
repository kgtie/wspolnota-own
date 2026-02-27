<?php

return [
    'access_ttl_seconds' => (int) env('API_ACCESS_TTL_SECONDS', 900),
    'refresh_ttl_days' => (int) env('API_REFRESH_TTL_DAYS', 30),
];
