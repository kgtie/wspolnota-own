<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\ContactMessageRequest;
use App\Mail\LandingContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('landing.index');
    }

    public function terms(): View
    {
        return view('landing.terms');
    }

    public function privacy(): View
    {
        return view('landing.privacy');
    }

    public function contact(): View
    {
        return view('landing.contact');
    }

    public function sendContact(ContactMessageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Mail::to(config('services.wspolnota.contact_recipient', 'konrad@wspolnota.app'))
            ->send(new LandingContactMessage(
                name: $validated['name'],
                email: $validated['email'],
                parish: $validated['parish'] ?? null,
                phone: $validated['phone'] ?? null,
                subjectLine: $validated['subject'],
                messageBody: $validated['message'],
            ));

        return redirect()
            ->route('landing.contact')
            ->with('status', 'Wiadomość została wysłana. Odezwiemy się możliwie szybko.');
    }
}
