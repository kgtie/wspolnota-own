<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunicationCampaignInlineMediaController extends Controller
{
    public function __invoke(Request $request, CommunicationCampaign $campaign): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $media = $campaign
            ->addMedia($validated['image'])
            ->toMediaCollection('content_images', 'news');

        $url = $media->hasGeneratedConversion('preview')
            ? $media->getUrl('preview')
            : $media->getUrl();

        return response()->json([
            'id' => $media->id,
            'url' => $url,
            'name' => $media->name,
        ]);
    }
}
