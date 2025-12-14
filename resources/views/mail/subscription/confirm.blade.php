<x-mail::message>

    <h1 class="header-text body-font"
        style="margin: 0 0 20px 0; font-size: 28px; line-height: 1.3; font-weight: 600; color: #1F2937;">
        Szczęść Boże! 👋
    </h1>

    <p class="body-font" style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #4B5563;">
        Dziękujemy za dołączenie do newslettera <strong>Wspólnoty</strong>. To dla nas
        ogromna radość, że chcesz śledzić rozwój nowoczesnych narzędzi dla Kościoła w Polsce.
    </p>

    <p class="body-font" style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: #4B5563;">
        Cieszymy się, że będziemy mogli dzielić się z Tobą informacjami o nadchodzącej Usłudze oraz o
        postępach prac. :)
    </p>

    <p class="body-font" style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: #4B5563;">
        Aby otrzymywać powiadomienia, prosimy jeszcze tylko o potwierdzenie adresu email. A jeśli prośba o zapisanie do
        newslettera nie wyszła od Ciebie - po prostu zignoruj tę wiadomość.
    </p>

    <x-mail::button :url="route('landing.mailing.confirm', ['token' => $subscriber->confirmation_token])" class="btn">

        To ja, zapisz mnie! :)

    </x-mail::button>

    <hr />

    <small>Jeśli nie chcesz otrzymywać już maili z tej listy ("Oczekujący na uruchomienie usługi Wspólnota") - możesz
        wypisać
        się z niej:
        <x-mail::button :url="route('landing.mailing.unsubscribe', ['token' => $subscriber->unsubscribe_token])">

            Wypisz mnie

        </x-mail::button>
    </small>
</x-mail::message>