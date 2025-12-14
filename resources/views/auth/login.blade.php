<x-guest-layout>
    <div class="text-center mb-4">
        <h4 class="fw-bold">Witaj</h4>
        <p class="text-muted small">Zaloguj się, aby kontynuować</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-floating mb-3">
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                value="{{ old('email') }}" placeholder="name@example.com" required autofocus>
            <label for="email">Adres Email</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-3">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                name="password" placeholder="Hasło" required>
            <label for="password">Hasło</label>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                <label class="form-check-label small text-muted" for="remember_me">
                    Zapamiętaj mnie
                </label>
            </div>
            @if (Route::has('password.request'))
                <a class="text-decoration-none small text-primary" href="{{ route('password.request') }}">
                    Zapomniałeś hasła?
                </a>
            @endif
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-md-primary btn-lg">
                Zaloguj się
            </button>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('register') }}" class="text-decoration-none small text-muted">
                Nie masz konta? Zarejestruj się
            </a>
        </div>
    </form>
</x-guest-layout>