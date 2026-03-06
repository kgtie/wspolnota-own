<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Parish;
use App\Support\Api\ApiResponder;

abstract class ApiController extends Controller
{
    use ApiResponder;

    protected function activeParishOrFail(int $parishId): Parish
    {
        return Parish::query()
            ->where('is_active', true)
            ->findOrFail($parishId);
    }
}
