<?php

namespace App\Http\Controllers\Api\V1\Parishes;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AnnouncementSet;
use App\Support\Announcements\AnnouncementSetPdfExporter;
use App\Support\Api\CursorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Obsługuje publiczne ogłoszenia parafialne, archiwum oraz eksport PDF.
 */
class AnnouncementController extends ApiController
{
    public function __construct(private readonly AnnouncementSetPdfExporter $pdfExporter) {}

    public function current(int $parishId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);

        $set = AnnouncementSet::query()
            ->where('parish_id', $parish->getKey())
            ->published()
            ->current()
            ->latest('effective_from')
            ->with('items')
            ->first();

        return $this->success([
            'announcement' => $set ? $this->payload($set, true) : null,
        ]);
    }

    public function index(Request $request, int $parishId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);
        $period = (string) $request->query('period', 'future');

        $query = AnnouncementSet::query()
            ->where('parish_id', $parish->getKey())
            ->published();

        if ($period === 'past') {
            $query->whereDate('effective_from', '<', now()->toDateString());
        } else {
            $query->whereDate('effective_from', '>=', now()->toDateString());
        }

        $paginated = CursorPaginator::paginate(
            query: $query,
            limit: 20,
            cursor: $request->query('cursor') ? (string) $request->query('cursor') : null,
            column: 'id',
            direction: 'desc',
        );

        return $this->collection(
            items: collect($paginated['items'])->map(fn (AnnouncementSet $set) => $this->payload($set))->all(),
            nextCursor: $paginated['next_cursor'],
            hasMore: $paginated['has_more'],
        );
    }

    public function pdf(int $parishId, int $packageId)
    {
        $parish = $this->activeParishOrFail($parishId);

        $set = AnnouncementSet::query()
            ->where('parish_id', $parish->getKey())
            ->published()
            ->findOrFail($packageId);

        return $this->pdfExporter->download($set);
    }

    private function payload(AnnouncementSet $set, bool $withItems = false): array
    {
        $payload = [
            'id' => (string) $set->getKey(),
            'parish_id' => (string) $set->parish_id,
            'title' => $set->title,
            'week_label' => $set->week_label,
            'lead' => $set->lead,
            'footer_notes' => $set->footer_notes,
            'summary_ai' => $set->summary_ai,
            'effective_from' => optional($set->effective_from)?->toDateString(),
            'effective_to' => optional($set->effective_to)?->toDateString(),
            'published_at' => optional($set->published_at)?->toISOString(),
            'created_at' => optional($set->created_at)?->toISOString(),
            'updated_at' => optional($set->updated_at)?->toISOString(),
            'pdf_url' => route('api.v1.announcements.pdf', [
                'parishId' => $set->parish_id,
                'packageId' => $set->getKey(),
            ]),
        ];

        if ($withItems) {
            $payload['items'] = $set->items->map(fn ($item) => [
                'id' => (string) $item->getKey(),
                'position' => (int) $item->position,
                'title' => $item->title,
                'content' => $item->content,
                'is_important' => (bool) $item->is_important,
            ])->values();
        }

        return $payload;
    }
}
