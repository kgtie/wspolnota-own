@extends('layouts.landing')

@section('title', 'Regulamin | Wspólnota')
@section('meta_description', 'Regulamin korzystania ze strony informacyjnej i usługi Wspólnota dla parafii i użytkowników aplikacji.')
@section('canonical', route('landing.terms'))

@section('content')
    <section class="mx-auto max-w-4xl space-y-6">
        <div class="space-y-3">
            <span class="eyebrow">Regulamin</span>
            <h1 class="font-display text-5xl text-stone-950">Zasady korzystania z usługi Wspólnota</h1>
            <p class="text-lg leading-8 text-stone-600">
                Niniejszy regulamin określa podstawowe zasady korzystania ze strony informacyjnej Wspólnota oraz z elektronicznych funkcji usługi kierowanej do parafii, administratorów i użytkowników aplikacji.
            </p>
        </div>

        <div class="legal-stack">
            <section class="legal-card">
                <h2>1. Czym jest Wspólnota</h2>
                <p>Wspólnota to usługa wspierająca parafie w komunikacji z parafianami, publikacji informacji duszpasterskich oraz prowadzeniu podstawowych procesów administracyjnych online.</p>
            </section>

            <section class="legal-card">
                <h2>2. Zakres funkcji</h2>
                <p>Usługa może obejmować w szczególności panel administratora parafii, aplikację PWA dla parafian, publikację mszy i intencji, ogłoszeń, aktualności oraz kancelarii online.</p>
            </section>

            <section class="legal-card">
                <h2>3. Korzystanie ze strony</h2>
                <p>Strona główna służy do prezentacji usługi, kontaktu oraz pozyskiwania zainteresowanych wdrożeniem. Użytkownik zobowiązuje się korzystać z serwisu w sposób zgodny z prawem, dobrymi obyczajami i bez naruszania bezpieczeństwa systemu.</p>
            </section>

            <section class="legal-card">
                <h2>4. Konta i dostęp</h2>
                <p>Dostęp do paneli administracyjnych wymaga aktywnego konta oraz odpowiednich uprawnień. Operator może ograniczyć lub zawiesić dostęp w razie naruszeń bezpieczeństwa, nadużyć albo konieczności technicznych.</p>
            </section>

            <section class="legal-card">
                <h2>5. Odpowiedzialność użytkownika</h2>
                <p>Administrator parafii odpowiada za poprawność treści publikowanych w ramach swojej parafii, legalność przesyłanych materiałów oraz właściwe korzystanie z danych wiernych i załączników.</p>
            </section>

            <section class="legal-card">
                <h2>6. Dostępność i zmiany</h2>
                <p>Operator rozwija usługę etapowo i może modyfikować funkcje, układ serwisu, model wdrożenia lub cennik. W przypadku wdrożeń komercyjnych szczegółowe warunki współpracy są ustalane indywidualnie.</p>
            </section>

            <section class="legal-card">
                <h2>7. Własność intelektualna</h2>
                <p>Treści, układ strony, oznaczenia oraz materiały przygotowane przez operatora podlegają ochronie prawnej. Bez zgody operatora nie wolno ich kopiować ani wykorzystywać poza dozwolonym użytkiem.</p>
            </section>

            <section class="legal-card">
                <h2>8. Reklamacje i kontakt</h2>
                <p>W sprawach dotyczących funkcjonowania strony lub usługi można kontaktować się mailowo pod adresem <a href="mailto:wspolnota@wspolnota.app" class="font-semibold text-stone-900 underline decoration-[#b8733380] underline-offset-4">wspolnota@wspolnota.app</a>. Zgłoszenia rozpatrywane są bez zbędnej zwłoki.</p>
            </section>

            <section class="legal-card">
                <h2>9. Postanowienia końcowe</h2>
                <p>Regulamin obowiązuje od dnia publikacji na stronie. Operator może go aktualizować wraz z rozwojem usługi i zmianami prawnymi lub technicznymi.</p>
            </section>
        </div>
    </section>
@endsection
