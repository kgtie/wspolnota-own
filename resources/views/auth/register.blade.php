<x-guest-layout>
    <div class="text-center mb-4">
        <h4 class="fw-bold">Utwórz konto</h4>
        <p class="text-muted small">Wybierz swoją parafię i dołącz do nas</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-floating mb-3">
            <select class="form-select @error('parish_id') is-invalid @enderror" id="parish_id" name="parish_id"
                aria-label="Wybierz parafię" style="background-color: transparent;">
                <option value="" selected disabled>Wybierz z listy...</option>
                @foreach($parishes as $parish)
                    <option value="{{ $parish->id }}">
                        {{ $parish->name }} ({{ $parish->city }})
                    </option>
                @endforeach
            </select>
            <label for="parish_id">Twoja Parafia</label>
            @error('parish_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-3">
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                value="{{ old('name') }}" placeholder="Jan Kowalski" required autofocus>
            <label for="name">Imię i Nazwisko</label>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-3">
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                value="{{ old('email') }}" placeholder="name@example.com" required>
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

        <div class="form-floating mb-4">
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                placeholder="Potwierdź hasło" required>
            <label for="password_confirmation">Potwierdź hasło</label>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-md-primary btn-lg">
                Zarejestruj się
            </button>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-decoration-none small text-muted">
                Masz już konto? Zaloguj się
            </a>
        </div>
    </form>
</x-guest-layout>