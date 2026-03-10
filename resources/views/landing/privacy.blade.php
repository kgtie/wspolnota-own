@extends('layouts.landing')

@section('title', 'Polityka prywatności | Wspólnota')
@section('meta_description', 'Polityka prywatności i informacje o cookies dla usługi Wspólnota.')

@section('content')
    <section class="mx-auto max-w-4xl space-y-6">
        <div class="space-y-3">
            <span class="eyebrow">Polityka prywatności</span>
            <h1 class="font-display text-5xl text-stone-950">Prywatność, dane i pliki cookies</h1>
            <p class="text-lg leading-8 text-stone-600">
                Wspólnota jest projektowana jako usługa do pracy z informacją wrażliwą i komunikacją parafialną, dlatego bezpieczeństwo danych traktujemy jako warunek działania, a nie dodatek.
            </p>
        </div>

        <div class="legal-stack">
            <section class="legal-card">
                <h2>1. Administrator i kontakt</h2>
                <p>W zakresie danych przetwarzanych przez stronę informacyjną administratorem jest operator usługi Wspólnota. Kontakt w sprawach prywatności: <a href="mailto:wspolnota@wspolnota.app" class="font-semibold text-stone-900 underline decoration-[#b8733380] underline-offset-4">wspolnota@wspolnota.app</a>.</p>
            </section>

            <section class="legal-card">
                <h2>2. Jakie dane możemy przetwarzać</h2>
                <p>Możemy przetwarzać dane kontaktowe przekazane przez użytkownika, dane kont administratorów i użytkowników usługi, dane techniczne dotyczące urządzenia i sesji, a także pliki i wiadomości przesyłane w ramach funkcji usługi.</p>
            </section>

            <section class="legal-card">
                <h2>3. Cele przetwarzania</h2>
                <p>Dane są wykorzystywane do obsługi strony i usługi, zapewnienia bezpieczeństwa, kontaktu w sprawach wdrożenia, realizacji funkcji aplikacji oraz do spełnienia obowiązków prawnych i organizacyjnych związanych z prowadzeniem serwisu.</p>
            </section>

            <section class="legal-card">
                <h2>4. Podstawy prawne</h2>
                <p>Przetwarzanie odbywa się odpowiednio na podstawie zgody, działań przed zawarciem umowy lub wykonania umowy, obowiązków prawnych oraz uzasadnionego interesu polegającego na zapewnieniu bezpieczeństwa, rozwoju i stabilności usługi.</p>
            </section>

            <section class="legal-card">
                <h2>5. Odbiorcy danych</h2>
                <p>Dane mogą być powierzane podmiotom wspierającym hosting, pocztę elektroniczną, utrzymanie infrastruktury, kopie bezpieczeństwa i obsługę techniczną. Dane nie są sprzedawane podmiotom trzecim.</p>
            </section>

            <section class="legal-card">
                <h2>6. Czas przechowywania</h2>
                <p>Dane przechowujemy przez okres niezbędny do realizacji celu, przez czas trwania współpracy lub konta, a także przez okres wymagany przepisami, bezpieczeństwem systemu albo potrzebą obrony przed roszczeniami.</p>
            </section>

            <section class="legal-card">
                <h2>7. Twoje prawa</h2>
                <p>Osobie, której dane dotyczą, przysługuje prawo dostępu do danych, ich sprostowania, usunięcia, ograniczenia przetwarzania, przenoszenia, sprzeciwu oraz wniesienia skargi do właściwego organu nadzorczego.</p>
            </section>

            <section class="legal-card">
                <h2>8. Cookies i podobne technologie</h2>
                <p>Serwis wykorzystuje pliki cookies niezbędne do działania, utrzymania sesji, bezpieczeństwa oraz zapamiętania podstawowych ustawień interfejsu, takich jak zamknięcie komunikatu cookies. Dodatkowe narzędzia analityczne lub wspierające mogą być wdrażane etapowo wraz z rozwojem usługi.</p>
            </section>

            <section class="legal-card">
                <h2>9. Bezpieczeństwo</h2>
                <p>Stosujemy rozwiązania organizacyjne i techniczne ograniczające ryzyko nieuprawnionego dostępu, utraty danych lub nadużycia. Zakres zabezpieczeń rozwijamy wraz z rozwojem produktu i charakterem przetwarzanych informacji.</p>
            </section>
        </div>
    </section>
@endsection
