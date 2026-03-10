@extends('layouts.landing')

@section('title', 'Kontakt | Wspólnota')
@section('meta_description', 'Kontakt z zespołem Wspólnota w sprawie wdrożenia usługi dla parafii, prezentacji i współpracy.')
@section('canonical', route('landing.contact'))
@section('schema_type', 'ContactPage')

@section('content')
    <section class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="space-y-4">
            <span class="eyebrow">Kontakt</span>
            <h1 class="font-display text-5xl text-stone-950">Porozmawiajmy o wdrożeniu Wspólnoty.</h1>
            <p class="text-lg leading-8 text-stone-600">
                Jeśli reprezentujesz parafię, chcesz zobaczyć kierunek produktu albo ustalić zakres pilotażu, napisz. Kontaktujemy się bez marketingowego hałasu, za to konkretnie.
            </p>
        </div>

        <div class="grid gap-5">
            <div class="panel px-6 py-6 sm:px-8">
                <div class="flex flex-col gap-2">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-stone-500">Formularz kontaktowy</p>
                    <h2 class="text-2xl font-bold text-stone-950">Napisz bezpośrednio do Konrada</h2>
                    <p class="text-sm leading-7 text-stone-600">Wiadomość z tego formularza trafi bezpośrednio na adres <span class="font-semibold text-stone-900">konrad@wspolnota.app</span>.</p>
                </div>

                <form action="{{ route('landing.contact.send') }}" method="POST" class="mt-6 grid gap-4">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="name" class="mb-2 block text-sm font-semibold text-stone-800">Imię i nazwisko</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" class="landing-input" required>
                            @error('name')
                                <p class="landing-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-stone-800">Adres e-mail</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="landing-input" required>
                            @error('email')
                                <p class="landing-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="parish" class="mb-2 block text-sm font-semibold text-stone-800">Parafia</label>
                            <input id="parish" name="parish" type="text" value="{{ old('parish') }}" class="landing-input" placeholder="Opcjonalnie">
                            @error('parish')
                                <p class="landing-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="phone" class="mb-2 block text-sm font-semibold text-stone-800">Telefon</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="landing-input" placeholder="Opcjonalnie">
                            @error('phone')
                                <p class="landing-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="mb-2 block text-sm font-semibold text-stone-800">Temat</label>
                        <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="landing-input" required>
                        @error('subject')
                            <p class="landing-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="message" class="mb-2 block text-sm font-semibold text-stone-800">Wiadomość</label>
                        <textarea id="message" name="message" rows="6" class="landing-input min-h-40" required>{{ old('message') }}</textarea>
                        @error('message')
                            <p class="landing-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm leading-6 text-stone-500">Odpowiadamy mailowo. Im więcej konkretów o parafii i zakresie wdrożenia, tym szybciej przejdziemy do sedna.</p>
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-stone-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#b87333]">
                            Wyślij wiadomość
                        </button>
                    </div>
                </form>
            </div>

            <div class="panel px-6 py-6 sm:px-8">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-stone-500">Email kontaktowy</p>
                <a href="mailto:wspolnota@wspolnota.app" class="mt-4 inline-flex text-2xl font-bold text-stone-950 underline decoration-[#b8733380] underline-offset-4">
                    wspolnota@wspolnota.app
                </a>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-stone-600">
                    Napisz, jeśli chcesz wdrożyć usługę, omówić pilotaż, przesłać pytania organizacyjne lub ustalić materiały do publikacji na stronie głównej.
                </p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div class="panel px-6 py-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-stone-500">Dla parafii</p>
                    <p class="mt-3 text-xl font-bold text-stone-950">Wdrożenia i prezentacje</p>
                    <p class="mt-3 text-sm leading-7 text-stone-600">Przygotujemy rozmowę o potrzebach parafii, zakresie modułów oraz sposobie startu.</p>
                </div>
                <div class="panel px-6 py-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-stone-500">Dla administratora</p>
                    <p class="mt-3 text-xl font-bold text-stone-950">Dostęp do panelu</p>
                    <p class="mt-3 text-sm leading-7 text-stone-600">Jeśli masz już konto, możesz przejść bezpośrednio do logowania i wejść do panelu proboszcza.</p>
                    <a href="{{ route('login') }}" class="mt-4 inline-flex rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-stone-900 hover:text-stone-950">
                        Przejdź do logowania
                    </a>
                </div>
            </div>

            <div class="panel px-6 py-6 sm:px-8">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-stone-500">Co warto napisać w pierwszej wiadomości</p>
                <ul class="mt-4 space-y-3 text-sm leading-7 text-stone-600">
                    <li>Nazwa parafii i miejscowość.</li>
                    <li>Czy interesuje Cię pilotaż, prezentacja czy gotowe wdrożenie.</li>
                    <li>Jakie moduły są dla Was najważniejsze: msze, ogłoszenia, aktualności czy kancelaria online.</li>
                </ul>
            </div>
        </div>
    </section>
@endsection
