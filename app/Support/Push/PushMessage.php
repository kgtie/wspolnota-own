<?php

namespace App\Support\Push;

class PushMessage
{
    /**
     * @param  array<string,string>  $data
     */
    public function __construct(
        public readonly string $token,
        public readonly string $platform,
        public readonly string $type,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data,
        public readonly ?string $collapseKey = null,
        public readonly string $priority = 'high',
        public readonly ?int $ttlSeconds = null,
    ) {}
}
