<x-guest-layout>
    <div class="text-center mb-4">
        <i class="fa-solid fa-key fa-3x text-primary mb-3"></i>
        <h4 class="fw-bold">Zapomniałeś hasła?</h4>
        <p class="text-muted small">
            To nie problem. Podaj nam swój adres email, a wyślemy Ci link, który pozwoli ustalić nowe hasło.
        </p>
    </div>

    @if (session('status'))
        <div class="alert alert-success small mb-4" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-floating mb-4">
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                value="{{ old('email') }}" placeholder="name@example.com" required autofocus>
            <label for="email">Adres Email</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-md-primary btn-lg">
                Wyślij link resetujący
            </button>

            <a href="{{ route('login') }}" class="btn btn-link text-decoration-none text-muted mt-2">
                <i class="fa-solid fa-arrow-left me-1"></i> Wróć do logowania
            </a>
        </div>
    </form>
</x-guest-layout>