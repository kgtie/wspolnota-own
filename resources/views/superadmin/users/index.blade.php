<x-superadmin-layout :pageInfo="['page.title' => 'Zarządzanie Użytkownikami', 'meta.description' => 'Zarządzanie Parafianami'],">

    {{-- Sekcja Filtrów i Akcji --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body">
            <form action="{{ route('superadmin.users.index') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Szukaj</label>
                        <input type="text" name="search" class="form-control" placeholder="Nazwa, email, parafia..."
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Rola</label>
                        <select name="role" class="form-select">
                            <option value="">Wszystkie role</option>
                            <option value="0" {{ request('role') === '0' ? 'selected' : '' }}>Użytkownik</option>
                            <option value="1" {{ request('role') === '1' ? 'selected' : '' }}>Administrator</option>
                            <option value="2" {{ request('role') === '2' ? 'selected' : '' }}>Superadmin</option>
                        </select>
                    </div>
                    <div class="col-md-5 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i>
                            Filtruj</button>
                        <a href="{{ route('superadmin.users.index') }}" class="btn btn-secondary"><i
                                class="fa-solid fa-eraser"></i> Wyczyść</a>
                        <a href="{{ route('superadmin.users.create') }}" class="btn btn-success text-nowrap"><i
                                class="fa-solid fa-plus"></i> Nowy User</a>
                        <a href="{{ route('superadmin.users.trash') }}" class="btn btn-warning text-nowrap"><i
                                class="fa-solid fa-trash"></i> Kosz</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="{{ route('superadmin.users.index', array_merge(request()->query(), ['sort' => 'id', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}"
                                    class="text-dark text-decoration-none">
                                    ID
                                    {!! request('sort') === 'id' ? (request('direction') === 'asc' ? '<i class="fa-solid fa-sort-up"></i>' : '<i class="fa-solid fa-sort-down"></i>') : '' !!}
                                </a>
                            </th>
                            <th>Użytkownik</th>
                            <th>Rola</th>
                            <th>Parafia Domowa</th>
                            <th>Weryfikacja</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($user->avatar)
                                            <img src="{{ Storage::disk('profiles')->url($user->avatar) }}"
                                                class="rounded-circle me-2 object-fit-cover" width="32" height="32">
                                        @else
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2"
                                                style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-bold">{{ $user->full_name ?? $user->name }}</div>
                                            <div class="small text-muted">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($user->role === 2) <span class="badge text-bg-danger">Superadmin</span>
                                    @elseif($user->role === 1) <span class="badge text-bg-primary">Administrator</span>
                                    @else <span class="badge text-bg-secondary">Parafianin</span> @endif
                                </td>
                                <td>
                                    @if($user->homeParish)
                                        <a href="#" class="text-decoration-none" title="{{ $user->homeParish->name }}">
                                            {{ $user->homeParish->short_name }}
                                        </a>
                                    @else
                                        <span class="text-muted small">Brak przypisania</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-column">
                                        <span
                                            class="badge {{ $user->email_verified_at ? 'text-bg-success' : 'text-bg-warning' }}"
                                            style="font-size: 0.65rem;">
                                            Email: {{ $user->email_verified_at ? 'OK' : 'NIE' }}
                                        </span>
                                        <span
                                            class="badge {{ $user->is_user_verified ? 'text-bg-success' : 'text-bg-danger' }}"
                                            style="font-size: 0.65rem;">
                                            Parafia: {{ $user->is_user_verified ? 'OK' : 'NIE' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('superadmin.users.edit', $user) }}"
                                        class="btn btn-sm btn-outline-primary" title="Edytuj">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form action="{{ route('superadmin.users.destroy', $user) }}" method="POST"
                                        class="d-inline-block"
                                        onsubmit="return confirm('Czy na pewno chcesz przenieść użytkownika do kosza?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń (Soft)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Brak wyników.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $users->links() }}
        </div>
    </div>
</x-superadmin-layout>