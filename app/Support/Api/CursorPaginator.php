<?php

namespace App\Support\Api;

use Illuminate\Database\Eloquent\Builder;

/**
 * Realizuje lekką paginację cursor-based dla list API bez użycia numerowanych stron.
 */
class CursorPaginator
{
    public static function paginate(Builder $query, int $limit = 20, ?string $cursor = null, string $column = 'id', string $direction = 'desc'): array
    {
        $limit = max(1, min($limit, 100));

        $cursorValue = self::decodeCursor($cursor);

        if ($cursorValue !== null) {
            $operator = $direction === 'desc' ? '<' : '>';
            $query->where($column, $operator, $cursorValue);
        }

        $items = $query->orderBy($column, $direction)->limit($limit + 1)->get();

        $hasMore = $items->count() > $limit;
        $items = $items->take($limit)->values();

        $nextCursor = null;

        if ($hasMore && $items->isNotEmpty()) {
            $last = $items->last();
            $nextCursor = self::encodeCursor((int) data_get($last, $column));
        }

        return [
            'items' => $items,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    public static function encodeCursor(int $value): string
    {
        return rtrim(strtr(base64_encode(json_encode(['id' => $value], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    public static function decodeCursor(?string $cursor): ?int
    {
        if (! $cursor) {
            return null;
        }

        $padding = strlen($cursor) % 4;
        if ($padding > 0) {
            $cursor .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);

        if (! is_string($decoded) || $decoded === '') {
            return null;
        }

        $payload = json_decode($decoded, true);

        if (! is_array($payload) || ! isset($payload['id']) || ! is_numeric($payload['id'])) {
            return null;
        }

        return (int) $payload['id'];
    }
}
