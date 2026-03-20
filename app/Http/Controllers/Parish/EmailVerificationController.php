<?php

namespace App\Http\Controllers\Parish;

use App\Http\Controllers\Controller;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class EmailVerificationController extends Controller
{
    public function __invoke(Request $request, Parish $subdomain, int $id, string $hash): Response
    {
        $parish = $subdomain;
        $publicContactVisibility = $parish->publicContactVisibility();
        $publicContact = $parish->publicContactData();
        $addressLines = collect([
            $parish->street,
            trim(collect([$parish->postal_code, $parish->city])->filter()->implode(' ')),
        ])->filter()->values();
        $user = User::query()
            ->whereKey($id)
            ->where('home_parish_id', $parish->getKey())
            ->firstOrFail();

        $status = 'verified';
        $httpStatus = 200;

        if (! $request->hasValidSignature() || ! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            $status = 'invalid';
            $httpStatus = 403;
        } elseif ($user->hasVerifiedEmail()) {
            $status = 'already_verified';
        } else {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        return response()->view('parish.auth.email-verification-result', [
            'parish' => $parish,
            'accentColor' => $this->normalizeAccentColor((string) $parish->getSetting('primary_color', '#b87333')),
            'avatarUrl' => $this->resolveParishMediaUrl($parish, 'avatar', 'thumb', 'avatar'),
            'coverImageUrl' => $this->resolveParishMediaUrl($parish, 'cover', 'preview', 'cover_image'),
            'publicEmail' => $publicContact['email'],
            'publicPhone' => $publicContact['phone'],
            'publicWebsiteUrl' => $publicContact['website'],
            'publicAddressLines' => $publicContact['address'] ? $addressLines : collect(),
            'websiteUrl' => $publicContact['website'],
            'addressLines' => $publicContactVisibility['address'] ? $addressLines : collect(),
            'status' => $status,
            'user' => $user->fresh(),
        ], $httpStatus);
    }

    private function resolveParishMediaUrl(
        Parish $parish,
        string $collection,
        string $conversion,
        string $legacyColumn,
    ): ?string {
        $mediaUrl = $parish->getFirstMediaUrl($collection, $conversion);

        if (filled($mediaUrl)) {
            return $mediaUrl;
        }

        $legacyPath = $parish->getAttribute($legacyColumn);

        if (! is_string($legacyPath) || blank($legacyPath)) {
            return null;
        }

        if (Str::startsWith($legacyPath, ['http://', 'https://', '/'])) {
            return $legacyPath;
        }

        return Storage::disk('profiles')->url($legacyPath);
    }

    private function normalizeAccentColor(string $color): string
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color)
            ? $color
            : '#b87333';
    }
}
