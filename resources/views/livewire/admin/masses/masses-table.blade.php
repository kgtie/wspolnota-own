<div>
    {{-- Filtry i Toolbar --}}
    <div class="p-3 border-bottom bg-light">
        <div class="row g-3 align-items-center">

            {{-- Filtrowanie widoku --}}
            <div class="col-md-4">
                <div class="btn-group w-100" role="group">
                    <button type="button"
                        class="btn btn-outline-secondary {{ $filterDate === 'upcoming' ? 'active' : '' }}"
                        wire:click="$set('filterDate', 'upcoming')">Nadchodzące</button>
                    <button type="button" class="btn btn-outline-secondary {{ $filterDate === 'past' ? 'active' : '' }}"
                        wire:click="$set('filterDate', 'past')">Archiwum</button>
                </div>
            </div>

            {{-- Wyszukiwarka --}}
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control"
                        placeholder="Szukaj w intencjach...">
                </div>
            </div>

            {{-- Generowanie PDF (Formularz klasyczny, nie Livewire action) --}}
            <div class="col-md-4 text-end">
                <form action="{{ route('admin.masses.print') }}" method="GET" target="_blank"
                    class="d-flex gap-2 justify-content-end align-items-center">
                    <input type="date" name="date_from" wire:model="printDateFrom" class="form-control form-control-sm"
                        style="width: auto;">
                    <span>-</span>
                    <input type="date" name="date_to" wire:model="printDateTo" class="form-control form-control-sm"
                        style="width: auto;">
                    <button type="submit" class="btn btn-sm btn-outline-dark" title="Drukuj listę/PDF">
                        <i class="fa-solid fa-print"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Lista Mszy --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data i Godzina</th>
                    <th style="width: 40%;">Intencja</th>
                    <th>Miejsce / Typ</th>
                    <th>Stypendium</th>
                    <th class="text-center">Zapisani</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($masses as $mass)
                    <tr wire:key="mass-row-{{ $mass->id }}"
                        class="{{ $mass->start_time < now() ? 'text-muted bg-light' : '' }}">
                        <td>
                            <div class="fw-bold">{{ $mass->start_time->format('Y-m-d') }} <span
                                    class="text-primary">{{ $mass->start_time->translatedFormat('l') }}</span></div>
                            <div class="fs-5">{{ $mass->start_time->format('H:i') }}</div>
                            @if($mass->celebrant)
                                <div class="small text-muted fst-italic">ks. {{ $mass->celebrant }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-break">{{ $mass->intention }}</div>
                        </td>
                        <td>
                            <div class="badge bg-secondary mb-1">{{ $mass->location }}</div><br>
                            <small>{{ $mass->type }} ({{ $mass->rite }})</small>
                        </td>
                        <td>
                            @if($mass->stipend)
                                <span class="text-success fw-bold">{{ $mass->stipend }} PLN</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button wire:click="openAttendance({{ $mass->id }})"
                                class="btn btn-sm btn-outline-info position-relative">
                                <i class="fa-solid fa-users"></i>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $mass->attendees()->count() }}
                                </span>
                            </button>
                        </td>
                        <td class="text-end">
                            <button wire:click="openEdit({{ $mass->id }})" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button wire:click="deleteMass({{ $mass->id }})"
                                wire:confirm="Czy na pewno chcesz usunąć tę mszę?" class="btn btn-sm btn-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-calendar-xmark fa-2x mb-3"></i><br>
                            Brak zaplanowanych mszy w tym widoku.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 border-top">
        {{ $masses->links() }}
    </div>
</div>