<?php

namespace App\Http\Controllers\Parish;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parish\StoreInterestRequest;
use App\Mail\ParishInterestMessage;
use App\Models\Parish;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class InterestController extends Controller
{
    public function store(StoreInterestRequest $request, Parish $subdomain): RedirectResponse
    {
        $parish = $subdomain;

        Mail::to(config('services.wspolnota.contact_recipient', 'konrad@wspolnota.app'))
            ->send(new ParishInterestMessage(
                parish: $parish,
                publicUrl: route('parish.home', ['subdomain' => $parish]),
                requestedAt: now(),
                requesterIp: $request->ip(),
                userAgent: $request->userAgent(),
            ));

        return back()->with('status', 'Dziękujemy. Zgłoszenie zostało zapisane, a my skontaktujemy się z parafią w sprawie uruchomienia usługi.');
    }
}
