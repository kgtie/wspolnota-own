<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Settings\GeneralSettings;
use Illuminate\Http\JsonResponse;

/**
 * Zwraca lekkie metadane usługi potrzebne klientowi mobilnemu.
 */
class MetaController extends Controller
{
    public function serviceVersion(GeneralSettings $settings): JsonResponse
    {
        return response()->json([
            'service_version' => (string) $settings->service_version,
        ]);
    }
}
