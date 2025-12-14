<x-guest-layout>
    <div class="text-center mb-4">
        <i class="fa-regular fa-envelope-open fa-3x text-primary mb-3"></i>
        <h4 class="fw-bold">Potwierdź adres email</h4>
        <p class="text-muted small">
            Dziękujemy za rejestrację! Przed rozpoczęciem i kontynuowaniem prosimy o kliknięcie w link, który właśnie
            wysłaliśmy na Twój
            adres email.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success small mb-4" role="alert">
            Nowy link weryfikacyjny został wysłany na adres podany podczas rejestracji.
        </div>
    @endif

    <div class="d-grid gap-2">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-md-primary w-100">
                Wyślij ponownie email weryfikacyjny
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link text-muted text-decoration-none w-100">
                Wyloguj się
            </button>
        </form>
    </div>
</x-guest-layout>