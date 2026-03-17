<?php

namespace App\Support\Push;

class PushSendResult
{
    /**
     * @param  array<string,mixed>  $response
     */
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $messageId = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly bool $shouldDisableDevice = false,
        public readonly array $response = [],
    ) {}

    /**
     * @param  array<string,mixed>  $response
     */
    public static function success(?string $messageId, array $response = []): self
    {
        return new self(
            successful: true,
            messageId: $messageId,
            response: $response,
        );
    }

    /**
     * @param  array<string,mixed>  $response
     */
    public static function failure(
        ?string $errorCode,
        ?string $errorMessage,
        bool $shouldDisableDevice = false,
        array $response = [],
    ): self {
        return new self(
            successful: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            shouldDisableDevice: $shouldDisableDevice,
            response: $response,
        );
    }
}
