<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Upload obrazów inline dla kreatora kampanii komunikacyjnych superadmina.
 *
 * Mimo że trasa żyje pod prefiksem /admin, kontroler sam pilnuje, by dostęp
 * miały tylko aktywne konta superadministracyjne.
 */
class CommunicationCampaignInlineMediaController extends Controller
{
    public function __invoke(Request $request, CommunicationCampaign $campaign): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin() || $user->status !== 'active') {
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
