<?php

namespace App\Services;

use App\Models\Mass;
use App\Models\Parish;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class MassPdfService
{
    public function weekly(Parish $parish, Carbon $from, Carbon $to)
    {
        $masses = Mass::query()
            ->where('parish_id', $parish->id)
            ->whereBetween('start_time', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('start_time')
            ->get();

        return Pdf::loadView('pdf.masses', [
            'parish' => $parish,
            'masses' => $masses,
            'from' => $from,
            'to' => $to,
        ])->setPaper('a4');
    }
}
