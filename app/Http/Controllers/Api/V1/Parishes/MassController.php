<?php

namespace App\Http\Controllers\Api\V1\Parishes;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Mass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MassController extends ApiController
{
    public function index(Request $request, int $parishId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);
        [$cursorCelebrationAt, $cursorId] = $this->decodeCursor($request->query('cursor'));

        $query = Mass::query()
            ->where('parish_id', $parish->getKey())
            ->whereBetween('celebration_at', [$validated['from'], $validated['to']]);

        if ($cursorCelebrationAt && $cursorId) {
            $query->where(function ($inner) use ($cursorCelebrationAt, $cursorId): void {
                $inner->where('celebration_at', '>', $cursorCelebrationAt)
                    ->orWhere(function ($sameMoment) use ($cursorCelebrationAt, $cursorId): void {
                        $sameMoment
                            ->where('celebration_at', '=', $cursorCelebrationAt)
                            ->where('id', '>', $cursorId);
                    });
            });
        }

        $limit = 20;
        $rows = $query
            ->orderBy('celebration_at')
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        [$items, $nextCursor, $hasMore] = $this->finalizePage($rows, $limit);

        return $this->collection(
            items: $items->map(fn (Mass $mass) => $this->payload($mass))->all(),
            nextCursor: $nextCursor,
            hasMore: $hasMore,
        );
    }

    public function show(int $parishId, int $massId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);

        $mass = Mass::query()
            ->where('parish_id', $parish->getKey())
            ->findOrFail($massId);

        return $this->success([
            'mass' => $this->payload($mass, true),
        ]);
    }

    private function payload(Mass $mass, bool $withAttendance = false): array
    {
        $payload = [
            'id' => (string) $mass->getKey(),
            'parish_id' => (string) $mass->parish_id,
            'intention_title' => $mass->intention_title,
            'intention_details' => $mass->intention_details,
            'celebration_at' => optional($mass->celebration_at)?->toISOString(),
            'mass_kind' => $mass->mass_kind,
            'mass_type' => $mass->mass_type,
            'status' => $mass->status,
            'celebrant_name' => $mass->celebrant_name,
            'location' => $mass->location,
            'created_at' => optional($mass->created_at)?->toISOString(),
            'updated_at' => optional($mass->updated_at)?->toISOString(),
        ];

        if ($withAttendance) {
            $payload['attendance_count'] = $mass->participants()->count();
        }

        return $payload;
    }

    private function finalizePage(Collection $rows, int $limit): array
    {
        $hasMore = $rows->count() > $limit;
        $items = $rows->take($limit)->values();
        $nextCursor = null;

        if ($hasMore && $items->isNotEmpty()) {
            /** @var Mass $last */
            $last = $items->last();
            $nextCursor = $this->encodeCursor(
                (string) optional($last->celebration_at)?->toISOString(),
                (int) $last->getKey(),
            );
        }

        return [$items, $nextCursor, $hasMore];
    }

    private function encodeCursor(string $celebrationAt, int $id): string
    {
        $json = json_encode([
            'celebration_at' => $celebrationAt,
            'id' => $id,
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    private function decodeCursor(mixed $cursor): array
    {
        if (! is_string($cursor) || $cursor === '') {
            return [null, null];
        }

        $padding = strlen($cursor) % 4;
        if ($padding > 0) {
            $cursor .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);

        if (! is_string($decoded) || $decoded === '') {
            return [null, null];
        }

        $payload = json_decode($decoded, true);

        if (! is_array($payload) || ! isset($payload['celebration_at'], $payload['id'])) {
            return [null, null];
        }

        return [(string) $payload['celebration_at'], (int) $payload['id']];
    }
}
