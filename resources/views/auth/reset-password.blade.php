<x-guest-layout>
    <div class="text-center mb-4">
        <h4 class="fw-bold">Ustaw nowe hasło</h4>
        <p class="text-muted small">Wprowadź nowe hasło dla swojego konta.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="form-floating mb-3">
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                value="{{ old('email', $request->email) }}" placeholder="name@example.com" required autofocus>
            <label for="email">Adres Email</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-3">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                name="password" placeholder="Nowe hasło" required>
            <label for="password">Nowe Hasło</label>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-4">
            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                id="password_confirmation" name="password_confirmation" placeholder="Potwierdź hasło" required>
            <label for="password_confirmation">Potwierdź Hasło</label>
            @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-md-primary btn-lg">
                Zresetuj hasło
            </button>
        </div>
    </form>
</x-guest-layout>