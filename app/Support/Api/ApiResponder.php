<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

trait ApiResponder
{
    protected function success(array $payload = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $payload,
        ], $status);
    }

    protected function collection(array $items, ?string $nextCursor = null, bool $hasMore = false): JsonResponse
    {
        return response()->json([
            'data' => $items,
            'meta' => [
                'next_cursor' => $nextCursor,
                'has_more' => $hasMore,
            ],
        ]);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function error(string $code, string $message, int $status, array $details = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => (object) $details,
            ],
        ], $status);
    }
}
