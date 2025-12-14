<?php

namespace App\Livewire\Landing;

use Livewire\Component;
use App\Models\MailingMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmSubscription;
class JoinWaitlist extends Component
{
    public $email = ''; // Pole na email
    public $statusMessage = ''; // Komunikat statusu
    public $statusType = ''; // success, warning, danger

    // ID listy "Oczekujący na usługę"
    public int $listId = 1;

    // Walidacja w czasie rzeczywistym
    protected $rules = [
        'email' => 'required|email'
    ];

    // Metoda wywoływana, gdy user pisze (debounce w widoku)
    public function updatedEmail()
    {
        $this->validateOnly('email');

        // Sprawdzenie "na żywo" czy mail istnieje
        // Szukamy także w usuniętych (withTrashed), żeby wiedzieć czy ktoś wraca
        $existing = MailingMail::withTrashed()
            ->where('mailing_list_id', $this->listId)
            ->where('email', $this->email)
            ->first();

        if ($existing && $existing->isConfirmed() && !$existing->trashed()) {
            $this->statusMessage = 'Ten adres jest już na liście i jest aktywny.';
            $this->statusType = 'success';
        } else {
            $this->reset(['statusMessage', 'statusType']);
        }
    }

    public function save()
    {
        $this->validate();

        // 1. Sprawdzamy czy subskrybent istnieje (także usunięty)
        $subscriber = MailingMail::withTrashed()
            ->where('mailing_list_id', $this->listId)
            ->where('email', $this->email)
            ->first();

        $token = Str::random(32);
        $unsubscribeToken = hash('md5', $this->email);

        if ($subscriber) {
            // SCENARIUSZ A: Już jest i jest potwierdzony
            if ($subscriber->isConfirmed() && !$subscriber->trashed()) {
                $this->statusMessage = 'Jesteś już zapisany na listę!';
                $this->statusType = 'success';
                return;
            }

            // SCENARIUSZ B: Wypisał się kiedyś (SoftDeleted) -> Przywracamy
            if ($subscriber->trashed()) {
                $subscriber->restore();
                $subscriber->confirmation_token = $token;
                $subscriber->unsubscribe_token = $unsubscribeToken;
                $subscriber->confirmed_at = null; // Wymagamy ponownego potwierdzenia
                $subscriber->save();
            }
            // SCENARIUSZ C: Jest w bazie, ale nigdy nie potwierdził maila
            else if (!$subscriber->isConfirmed()) {
                $subscriber->confirmation_token = $token;
                $subscriber->unsubscribe_token = $unsubscribeToken;
                $subscriber->save();
            }

        } else {
            // SCENARIUSZ D: Nowy użytkownik
            $subscriber = MailingMail::create([
                'mailing_list_id' => $this->listId,
                'email' => $this->email,
                'confirmation_token' => $token,
                'unsubscribe_token' => $unsubscribeToken,
            ]);
        }

        // Wyślij maila
        Mail::to($subscriber->email)->send(new ConfirmSubscription($subscriber));

        $this->statusMessage = 'Sprawdź skrzynkę pocztową i potwierdź zapis!';
        $this->statusType = 'success';
        $this->email = '';
    }

    public function render()
    {
        return view('livewire.landing.join-waitlist');
    }
}