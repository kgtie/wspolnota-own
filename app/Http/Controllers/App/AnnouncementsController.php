<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementSet;
use App\Models\Parish;

class AnnouncementsController extends Controller
{
    public function index(Parish $parish)
    {
        $publishedSets = AnnouncementSet::query()
            ->where('parish_id', $parish->id)
            ->where('status', 'published')
            ->with([
                'items' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('position'),
            ])
            ->orderByDesc('effective_from')
            ->limit(20)
            ->get();

        $currentSet = $publishedSets
            ->first(fn (AnnouncementSet $set) => $set->effective_from?->lte(today()) && ($set->effective_to === null || $set->effective_to->gte(today())))
            ?? $publishedSets->first();

        $pageInfo = [
            'meta.title' => 'Ogłoszenia parafialne',
            'meta.description' => 'Ogłoszenia parafialne w Usłudze Wspólnota dla parafii '.$parish->name,
            'page.title' => 'Ogłoszenia parafialne',
        ];

        return view('app.announcements.index', [
            'currentParish' => $parish,
            'pageInfo' => $pageInfo,
            'currentSet' => $currentSet,
            'publishedSets' => $publishedSets,
        ]);
    }
}
