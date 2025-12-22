<x-superadmin-layout :pageInfo="['page.title' => 'Dodaj Nową Mszę', 'meta.description' => 'Tworzenie mszy']">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title">Formularz dodawania mszy</h3>
                    <div class="card-tools">
                        <a href="{{ route('superadmin.masses.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Powrót
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.masses.store') }}">
                        @csrf

                        {{-- Wybór Parafii (Kluczowe dla Superadmina) --}}
                        <div class="mb-4 bg-light p-3 rounded border">
                            <label class="form-label fw-bold">Parafia <span class="text-danger">*</span></label>
                            <select name="parish_id" class="form-select @error('parish_id') is-invalid @enderror"
                                required>
                                <option value="">-- Wybierz parafię --</option>
                                @foreach($parishes as $p)
                                    <option value="{{ $p->id }}" {{ old('parish_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->city }})
                                    </option>
                                @endforeach
                            </select>
                            @error('parish_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-3">
                            {{-- Czas i Miejsce --}}
                            <div class="col-md-6">
                                <label class="form-label">Data <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                    value="{{ old('date', now()->addDay()->format('Y-m-d')) }}" required>
                                @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Godzina <span class="text-danger">*</span></label>
                                <input type="time" name="time" class="form-control @error('time') is-invalid @enderror"
                                    value="{{ old('time', '18:00') }}" required>
                                @error('time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Miejsce <span class="text-danger">*</span></label>
                                <input type="text" name="location" class="form-control"
                                    value="{{ old('location', 'Kościół główny') }}" list="locations">
                                <datalist id="locations">
                                    <option value="Kościół główny">
                                    <option value="Kaplica">
                                    <option value="Cmentarz">
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-success fw-bold">Ofiara (PLN)</label>
                                <input type="number" step="0.01" name="stipend" class="form-control"
                                    value="{{ old('stipend') }}" placeholder="0.00">
                            </div>

                            {{-- Szczegóły Liturgiczne --}}
                            <div class="col-12 mt-4">
                                <h6 class="border-bottom pb-2 mb-3">Szczegóły liturgiczne</h6>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Intencja <span class="text-danger">*</span></label>
                                <textarea name="intention" class="form-control @error('intention') is-invalid @enderror"
                                    rows="3" required>{{ old('intention') }}</textarea>
                                @error('intention') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Rodzaj mszy</label>
                                <select name="type" class="form-select">
                                    <option value="z dnia" {{ old('type') == 'z dnia' ? 'selected' : '' }}>Z dnia</option>
                                    <option value="Niedzielna" {{ old('type') == 'Niedzielna' ? 'selected' : '' }}>
                                        Niedzielna</option>
                                    <option value="Uroczystość" {{ old('type') == 'Uroczystość' ? 'selected' : '' }}>
                                        Uroczystość</option>
                                    <option value="Pogrzebowa" {{ old('type') == 'Pogrzebowa' ? 'selected' : '' }}>
                                        Pogrzebowa</option>
                                    <option value="Ślubna" {{ old('type') == 'Ślubna' ? 'selected' : '' }}>Ślubna</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Ryt</label>
                                <select name="rite" class="form-select">
                                    <option value="rzymski" {{ old('rite') == 'rzymski' ? 'selected' : '' }}>Rzymski
                                    </option>
                                    <option value="trydencki" {{ old('rite') == 'trydencki' ? 'selected' : '' }}>Trydencki
                                    </option>
                                    <option value="grekokatolicki" {{ old('rite') == 'grekokatolicki' ? 'selected' : '' }}>Grekokatolicki</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Celebrans (Ksiądz)</label>
                                <input type="text" name="celebrant" class="form-control" value="{{ old('celebrant') }}"
                                    placeholder="np. ks. Proboszcz">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa-solid fa-save"></i> Zapisz Mszę
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-superadmin-layout>