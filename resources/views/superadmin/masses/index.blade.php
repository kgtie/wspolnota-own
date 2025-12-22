<x-superadmin-layout :pageInfo="['page.title' => 'Zarządzanie Mszami (Globalne)', 'meta.description' => 'Zarządzanie mszami']">

    {{-- Sekcja Filtrów --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-filter"></i> Filtrowanie zaawansowane</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('superadmin.masses.index') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Szukaj (tekst)</label>
                        <input type="text" name="search" class="form-control" placeholder="Intencja, ksiądz, miasto..."
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Parafia</label>
                        <select name="parish_id" class="form-select">
                            <option value="">-- Wszystkie --</option>
                            @foreach($parishes as $p)
                                <option value="{{ $p->id }}" {{ request('parish_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ $p->city }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data od</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data do</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i>
                                Szukaj</button>
                            <a href="{{ route('superadmin.masses.index') }}"
                                class="btn btn-outline-secondary btn-sm">Reset</a>
                        </div>
                    </div>
                </div>
                {{-- Ukryte pola do zachowania sortowania przy filtrowaniu --}}
                <input type="hidden" name="sort" value="{{ request('sort', 'start_time') }}">
                <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
            </form>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="card shadow-sm">
        <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title">Wyniki wyszukiwania ({{ $masses->total() }})</h3>
            <a href="{{ route('superadmin.masses.create') }}" class="btn btn-success">
                <i class="fa-solid fa-plus"></i> Nowa Msza
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="{{ route('superadmin.masses.index', array_merge(request()->query(), ['sort' => 'start_time', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}"
                                    class="text-dark text-decoration-none">
                                    Data i Czas
                                    {!! request('sort') === 'start_time' ? (request('direction') === 'asc' ? '↑' : '↓') : '' !!}
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('superadmin.masses.index', array_merge(request()->query(), ['sort' => 'parish_name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}"
                                    class="text-dark text-decoration-none">
                                    Parafia
                                    {!! request('sort') === 'parish_name' ? (request('direction') === 'asc' ? '↑' : '↓') : '' !!}
                                </a>
                            </th>
                            <th style="width: 30%;">Intencja</th>
                            <th>Typ / Ryt</th>
                            <th>Finanse</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($masses as $mass)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $mass->start_time->format('Y-m-d') }}</div>
                                    <div class="fs-5">{{ $mass->start_time->format('H:i') }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary">{{ $mass->parish->name }}</div>
                                    <div class="small text-muted">{{ $mass->parish->city }}</div>
                                    <div class="badge bg-secondary">{{ $mass->location }}</div>
                                </td>
                                <td>
                                    {{ Str::limit($mass->intention, 80) }}
                                    @if($mass->celebrant)
                                        <div class="small text-muted fst-italic mt-1">Cel: {{ $mass->celebrant }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">{{ $mass->type }}</span>
                                    <div class="small text-muted mt-1">{{ $mass->rite }}</div>
                                </td>
                                <td>
                                    @if($mass->stipend)
                                        <span class="text-success fw-bold">{{ $mass->stipend }} PLN</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('superadmin.masses.edit', $mass) }}"
                                        class="btn btn-sm btn-outline-primary" title="Edytuj">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form action="{{ route('superadmin.masses.destroy', $mass) }}" method="POST"
                                        class="d-inline-block"
                                        onsubmit="return confirm('Czy na pewno chcesz usunąć tę mszę?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń trwale">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Brak mszy spełniających kryteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $masses->links() }}
        </div>
    </div>
</x-superadmin-layout>