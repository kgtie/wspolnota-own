<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\ContactMessageRequest;
use App\Mail\LandingContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
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
            ->queue(new LandingContactMessage(
                name: $validated['name'],
                email: $validated['email'],
                parish: $validated['parish'] ?? null,
                phone: $validated['phone'] ?? null,
                subjectLine: $validated['subject'],
                messageBody: $validated['message'],
            ));

        return redirect()
            ->route('landing.contact')
            ->with('status', 'Wiadomość została przyjęta do wysyłki. Odezwiemy się możliwie szybko.');
    }

    public function sitemap(): Response
    {
        $pages = [
            [
                'loc' => route('landing.home'),
                'lastmod' => $this->viewLastModified('landing/index.blade.php'),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => route('landing.contact'),
                'lastmod' => $this->viewLastModified('landing/contact.blade.php'),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('landing.privacy'),
                'lastmod' => $this->viewLastModified('landing/privacy.blade.php'),
                'changefreq' => 'yearly',
                'priority' => '0.4',
            ],
            [
                'loc' => route('landing.terms'),
                'lastmod' => $this->viewLastModified('landing/terms.blade.php'),
                'changefreq' => 'yearly',
                'priority' => '0.4',
            ],
        ];

        return response()
            ->view('landing.sitemap', compact('pages'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return response()
            ->view('landing.robots', [
                'baseUrl' => rtrim(config('app.url'), '/'),
            ])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    protected function viewLastModified(string $relativePath): string
    {
        return gmdate('Y-m-d', filemtime(resource_path('views/'.$relativePath)));
    }
}
