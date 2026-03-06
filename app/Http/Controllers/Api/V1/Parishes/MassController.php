<?php

namespace App\Http\Controllers\Api\V1\Parishes;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Mass;
use App\Support\Api\CursorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MassController extends ApiController
{
    public function index(Request $request, int $parishId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);
        $query = Mass::query()->where('parish_id', $parish->getKey());

        if ($request->filled('from')) {
            $query->where('celebration_at', '>=', (string) $request->string('from'));
        }

        if ($request->filled('to')) {
            $query->where('celebration_at', '<=', (string) $request->string('to'));
        }

        $paginated = CursorPaginator::paginate(
            query: $query,
            limit: 20,
            cursor: $request->query('cursor') ? (string) $request->query('cursor') : null,
            column: 'id',
            direction: 'desc',
        );

        return $this->collection(
            items: collect($paginated['items'])->map(fn (Mass $mass) => $this->payload($mass))->all(),
            nextCursor: $paginated['next_cursor'],
            hasMore: $paginated['has_more'],
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
}
