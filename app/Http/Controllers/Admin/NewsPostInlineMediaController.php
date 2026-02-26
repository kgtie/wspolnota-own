<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsPostInlineMediaController extends Controller
{
    public function __invoke(Request $request, NewsPost $newsPost): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
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
