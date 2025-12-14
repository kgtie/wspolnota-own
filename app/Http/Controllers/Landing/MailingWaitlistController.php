<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MailingMail;

class MailingWaitlistController extends Controller
{
    public function confirm($token)
    {
        $subscriber = MailingMail::where('confirmation_token', $token)->first();
        if ($subscriber) {
            if (!$subscriber->confirmed_at) {
                $subscriber->update([
                    'confirmed_at' => now(),
                    'confirmation_token' => null // Token zużyty
                ]);
                return redirect('/')->with('status', 'Dziękujemy! Twój email został potwierdzony.');
            }
        }
        return redirect('/')->with('status', 'Przekierowano na stronę główną.');
    }

    public function unsubscribe($token)
    {
        $subscriber = MailingMail::where('unsubscribe_token', $token)->first();
        if ($subscriber) {
            if (!$subscriber->deleted_at) {
                $subscriber->delete();
                return redirect('/')->with('status', 'Zostałeś wypisany z listy mailingowej.');
            }
        }
        return redirect('/')->with('status', 'Przekierowano na stronę główną.');
    }
}