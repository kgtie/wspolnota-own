<x-superadmin-layout :pageInfo="['page.title' => 'Edycja Użytkownika', 'meta.description' => 'Edycja parafian']">

    <div class="row">
        <div class="col-md-12">
            <a href="{{ route('superadmin.users.index') }}" class="btn btn-outline-secondary mb-3">
                <i class="fa-solid fa-arrow-left"></i> Powrót do listy
            </a>
        </div>

        <div class="col-md-4">
            {{-- Karta informacyjna --}}
            <div class="card card-primary card-outline mb-4">
                <div class="card-body box-profile text-center">
                    @if($user->avatar)
                        <img class="profile-user-img img-fluid img-circle"
                            src="{{ Storage::disk('profiles')->url($user->avatar) }}" alt="Avatar">
                    @else
                        <div class="profile-user-img img-fluid img-circle bg-secondary d-flex align-items-center justify-content-center text-white mx-auto"
                            style="width: 100px; height: 100px; font-size: 2rem;">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                    @endif

                    <h3 class="profile-username text-center mt-3">{{ $user->full_name ?? $user->name }}</h3>
                    <p class="text-muted text-center">{{ $user->email }}</p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <b>Rola</b>
                            <span>
                                @if($user->role === 2) Superadmin
                                @elseif($user->role === 1) Administrator
                                @else Użytkownik @endif
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>Kod weryfikacyjny</b>
                            <span class="font-monospace">{{ $user->verification_code ?? '---' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>Data rejestracji</b>
                            <span>{{ $user->created_at->format('Y-m-d') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#details" data-bs-toggle="tab">Dane
                                podstawowe</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" method="POST" action="{{ route('superadmin.users.update', $user) }}"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Sekcja 1: Tożsamość --}}
                        <h6 class="text-primary border-bottom pb-2 mb-3">Tożsamość</h6>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Login (Name)</label>
                            <div class="col-sm-9">
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $user->name) }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Imię i Nazwisko</label>
                            <div class="col-sm-9">
                                <input type="text" name="full_name" class="form-control"
                                    value="{{ old('full_name', $user->full_name) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $user->email) }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nowe hasło</label>
                            <div class="col-sm-9">
                                <input type="password" name="password" class="form-control"
                                    placeholder="Wypełnij tylko, jeśli chcesz zmienić">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Potwierdź hasło</label>
                            <div class="col-sm-9">
                                <input type="password" name="password_confirmation" class="form-control"
                                    placeholder="Potwierdź nowe hasło">
                            </div>
                        </div>

                        {{-- Sekcja 2: Uprawnienia i Przynależność --}}
                        <h6 class="text-primary border-bottom pb-2 mb-3 mt-4">Uprawnienia i Przynależność</h6>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Rola w systemie</label>
                            <div class="col-sm-9">
                                <select name="role" class="form-select">
                                    <option value="0" {{ old('role', $user->role) == 0 ? 'selected' : '' }}>Użytkownik
                                        (Parafianin)</option>
                                    <option value="1" {{ old('role', $user->role) == 1 ? 'selected' : '' }}>Administrator
                                        (Proboszcz)</option>
                                    <option value="2" {{ old('role', $user->role) == 2 ? 'selected' : '' }}>
                                        Superadministrator</option>
                                </select>
                                <div class="form-text text-muted">Zmiana roli na wyższą daje ogromne uprawnienia!</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Parafia Domowa</label>
                            <div class="col-sm-9">
                                <select name="home_parish_id" class="form-select">
                                    <option value="">-- Brak / Nie przypisano --</option>
                                    @foreach($parishes as $parish)
                                        <option value="{{ $parish->id }}" {{ old('home_parish_id', $user->home_parish_id) == $parish->id ? 'selected' : '' }}>
                                            {{ $parish->name }} ({{ $parish->city }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Sekcja 3: Władcza Weryfikacja (God Mode) --}}
                        <h6 class="text-danger border-bottom pb-2 mb-3 mt-4"><i class="fa-solid fa-bolt"></i> Strefa
                            Władcza (God Mode)</h6>

                        <div class="row mb-3">
                            <div class="col-sm-9 offset-sm-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="checkEmail"
                                        name="is_email_verified" value="1" {{ $user->email_verified_at ? 'checked' : '' }}>
                                    <label class="form-check-label" for="checkEmail">Email zweryfikowany (wymuś
                                        potwierdzenie)</label>
                                </div>
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="checkParish"
                                        name="is_parish_verified" value="1" {{ $user->is_user_verified ? 'checked' : '' }}>
                                    <label class="form-check-label" for="checkParish">Użytkownik zatwierdzony w parafii
                                        (obejdź kod 9 cyfr)</label>
                                </div>
                            </div>
                        </div>

                        {{-- Sekcja 4: Avatar --}}
                        <h6 class="text-primary border-bottom pb-2 mb-3 mt-4">Media</h6>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Avatar</label>
                            <div class="col-sm-9">
                                <input type="file" name="avatar_file" class="form-control">
                                @if($user->avatar)
                                    <div class="form-check mt-2">
                                        <input type="checkbox" class="form-check-input" id="removeAvatar"
                                            name="avatar_remove" value="1">
                                        <label class="form-check-label text-danger" for="removeAvatar">Usuń obecny
                                            avatar</label>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-save"></i> Zapisz zmiany
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-superadmin-layout>