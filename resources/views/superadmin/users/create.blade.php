<x-superadmin-layout :pageInfo="['page.title' => 'Dodaj Nowego Użytkownika', 'meta.description' => 'Tworzenie nowego parafianina']">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title">Formularz rejestracji</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.users.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Dane podstawowe --}}
                        <div class="mb-3">
                            <label class="form-label">Login (Name) <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Imię i Nazwisko</label>
                            <input type="text" name="full_name"
                                class="form-control @error('full_name') is-invalid @enderror"
                                value="{{ old('full_name') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hasło <span class="text-danger">*</span></label>
                                <input type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror" required>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Potwierdź hasło</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>
                        </div>

                        {{-- Rola i Parafia --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rola</label>
                                <select name="role" class="form-select">
                                    <option value="0">Użytkownik</option>
                                    <option value="1">Administrator</option>
                                    <option value="2">Superadmin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parafia Domowa</label>
                                <select name="home_parish_id" class="form-select">
                                    <option value="">-- Brak --</option>
                                    @foreach($parishes as $parish)
                                        <option value="{{ $parish->id }}">{{ $parish->name }} ({{ $parish->city }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Checkboxy startowe --}}
                        <div class="mb-3 bg-light p-3 rounded">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_email_verified" value="1"
                                    id="c1" checked>
                                <label class="form-check-label" for="c1">Oznacz email jako zweryfikowany</label>
                            </div>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" name="is_parish_verified" value="1"
                                    id="c2">
                                <label class="form-check-label" for="c2">Zatwierdź od razu w parafii</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('superadmin.users.index') }}" class="btn btn-default">Anuluj</a>
                            <button type="submit" class="btn btn-success"><i class="fa-solid fa-plus"></i> Utwórz
                                Użytkownika</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-superadmin-layout>