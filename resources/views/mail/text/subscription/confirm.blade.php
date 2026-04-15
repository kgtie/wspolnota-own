Potwierdz zapis do Wspolnoty

Dziekujemy za dolaczenie do newslettera Wspolnoty.
Potwierdzenie zapisu: {{ route('landing.mailing.confirm', ['token' => $subscriber->confirmation_token]) }}
Wypisanie: {{ route('landing.mailing.unsubscribe', ['token' => $subscriber->unsubscribe_token]) }}
