<x-superadmin-layout :pageInfo="['page.title' => 'Edycja Mszy', 'meta.description' => 'Edycja mszy']">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Edycja danych mszy #{{ $mass->id }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('superadmin.masses.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Powrót
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.masses.update', $mass) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4 bg-light p-3 rounded border">
                            <label class="form-label fw-bold">Parafia</label>
                            <select name="parish_id" class="form-select" required>
                                @foreach($parishes as $p)
                                    <option value="{{ $p->id }}" {{ old('parish_id', $mass->parish_id) == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->city }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-danger">Uwaga: Zmiana parafii przeniesie mszę do innego
                                kalendarza!</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Data</label>
                                <input type="date" name="date" class="form-control"
                                    value="{{ old('date', $mass->start_time->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Godzina</label>
                                <input type="time" name="time" class="form-control"
                                    value="{{ old('time', $mass->start_time->format('H:i')) }}" required>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Miejsce</label>
                                <input type="text" name="location" class="form-control"
                                    value="{{ old('location', $mass->location) }}" list="locations">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-success fw-bold">Ofiara (PLN)</label>
                                <input type="number" step="0.01" name="stipend" class="form-control"
                                    value="{{ old('stipend', $mass->stipend) }}">
                            </div>

                            <div class="col-12 mt-4">
                                <h6 class="border-bottom pb-2 mb-3">Szczegóły liturgiczne</h6>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Intencja</label>
                                <textarea name="intention" class="form-control" rows="3"
                                    required>{{ old('intention', $mass->intention) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Rodzaj mszy</label>
                                <select name="type" class="form-select">
                                    @foreach(['z dnia', 'Niedzielna', 'Uroczystość', 'Pogrzebowa', 'Ślubna'] as $t)
                                        <option value="{{ $t }}" {{ old('type', $mass->type) == $t ? 'selected' : '' }}>
                                            {{ $t }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Ryt</label>
                                <select name="rite" class="form-select">
                                    <option value="rzymski" {{ old('rite', $mass->rite) == 'rzymski' ? 'selected' : '' }}>
                                        Rzymski</option>
                                    <option value="trydencki" {{ old('rite', $mass->rite) == 'trydencki' ? 'selected' : '' }}>Trydencki</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Celebrans</label>
                                <input type="text" name="celebrant" class="form-control"
                                    value="{{ old('celebrant', $mass->celebrant) }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-save"></i> Zaktualizuj
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-superadmin-layout>