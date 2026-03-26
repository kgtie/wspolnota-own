<?php

namespace App\Http\Controllers\Api\V1\Parishes;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\NewsPost;
use App\Models\Parish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dostarcza publiczne dane parafii oraz centralny payload home-feed dla aplikacji.
 */
class ParishController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Parish::query()->where('is_active', true);

        if ($request->filled('query')) {
            $term = (string) $request->string('query');
            $query->where(function ($inner) use ($term): void {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('short_name', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        $parishes = $query->orderBy('short_name')->limit(50)->get();

        return $this->success([
            'items' => $parishes->map(fn (Parish $parish) => $this->parishPayload($parish))->values(),
        ]);
    }

    public function show(int $parishId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);

        return $this->success([
            'parish' => $this->parishPayload($parish),
        ]);
    }

    public function homeFeed(int $parishId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);

        $nextMasses = Mass::query()
            ->where('parish_id', $parish->getKey())
            ->where('status', 'scheduled')
            ->where('celebration_at', '>=', now())
            ->orderBy('celebration_at')
            ->limit(5)
            ->get();

        $currentAnnouncements = AnnouncementSet::query()
            ->where('parish_id', $parish->getKey())
            ->published()
            ->current()
            ->latest('effective_from')
            ->first();

        $latestNews = NewsPost::query()
            ->where('parish_id', $parish->getKey())
            ->published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        return $this->success([
            'parish' => $this->parishPayload($parish),
            'next_masses' => $nextMasses->map(fn (Mass $mass) => $this->massPayload($mass))->values(),
            'current_announcements' => $currentAnnouncements
                ? $this->announcementPayload($currentAnnouncements)
                : null,
            'latest_news' => $latestNews->map(fn (NewsPost $post) => $this->newsPayload($post))->values(),
        ]);
    }

    private function parishPayload(Parish $parish): array
    {
        $contactVisibility = $parish->publicContactVisibility();
        $publicContact = $parish->publicContactData();

        return [
            'id' => (string) $parish->getKey(),
            'name' => $parish->name,
            'short_name' => $parish->short_name,
            'slug' => $parish->slug,
            'email' => $publicContact['email'],
            'phone' => $publicContact['phone'],
            'website' => $publicContact['website'],
            'street' => $publicContact['address']['street'] ?? null,
            'postal_code' => $publicContact['address']['postal_code'] ?? null,
            'city' => $parish->city,
            'diocese' => $parish->diocese,
            'decanate' => $parish->decanate,
            'contact_visibility' => $contactVisibility,
            'public_contact' => [
                'email' => $publicContact['email'],
                'phone' => $publicContact['phone'],
                'website' => $publicContact['website'],
                'address' => $publicContact['address'],
            ],
            'staff_members' => $parish->staff_members_list,
            'is_active' => (bool) $parish->is_active,
            'avatar_url' => $parish->avatar_url,
            'cover_url' => $parish->cover_url,
        ];
    }

    private function massPayload(Mass $mass): array
    {
        return [
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
    }

    private function announcementPayload(AnnouncementSet $set): array
    {
        return [
            'id' => (string) $set->getKey(),
            'parish_id' => (string) $set->parish_id,
            'title' => $set->title,
            'week_label' => $set->week_label,
            'lead' => $set->lead,
            'summary_ai' => $set->summary_ai,
            'effective_from' => optional($set->effective_from)?->toDateString(),
            'effective_to' => optional($set->effective_to)?->toDateString(),
            'published_at' => optional($set->published_at)?->toISOString(),
            'pdf_url' => route('api.v1.announcements.pdf', [
                'parishId' => $set->parish_id,
                'packageId' => $set->getKey(),
            ]),
        ];
    }

    private function newsPayload(NewsPost $post): array
    {
        return [
            'id' => (string) $post->getKey(),
            'parish_id' => (string) $post->parish_id,
            'title' => $post->title,
            'slug' => $post->slug,
            'is_pinned' => (bool) $post->is_pinned,
            'featured_image_url' => $post->getFirstMediaUrl('featured_image', 'preview') ?: null,
            'published_at' => optional($post->published_at)?->toISOString(),
            'created_at' => optional($post->created_at)?->toISOString(),
            'updated_at' => optional($post->updated_at)?->toISOString(),
        ];
    }
}
