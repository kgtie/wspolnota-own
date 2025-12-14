<x-guest-layout>
    <div class="text-center mb-4">
        <i class="fa-solid fa-shield-halved fa-3x text-primary mb-3"></i>
        <h4 class="fw-bold">Potwierdź dostęp</h4>
        <p class="text-muted small">
            To bezpieczna strefa aplikacji. Potwierdź swoje hasło, aby kontynuować.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="form-floating mb-4">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                name="password" placeholder="Hasło" required autofocus>
            <label for="password">Hasło</label>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-md-primary btn-lg">
                Potwierdź
            </button>
        </div>
    </form>
</x-guest-layout>