<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Obsługuje upload obrazów osadzanych bezpośrednio w treści aktualności.
 *
 * Endpoint nie polega wyłącznie na middleware. Dodatkowo weryfikuje aktywny
 * status konta i realne prawo zarządzania parafią, do której należy wpis.
 */
class NewsPostInlineMediaController extends Controller
{
    public function __invoke(Request $request, NewsPost $newsPost): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || $user->status !== 'active') {
            abort(403);
        }

        $canManageParish = $user->managedParishes()
            ->wherePivot('is_active', true)
            ->whereKey($newsPost->parish_id)
            ->exists();

        if (! $canManageParish) {
            abort(403);
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $media = $newsPost
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
