<div>
    {{-- Pasek narzędzi: Wyszukiwanie i Filtry --}}
    <div class="p-3 border-bottom bg-light">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control"
                        placeholder="Szukaj (imię, login, email)...">
                </div>
            </div>
            <div class="col-md-3">
                <select wire:model.live="filterEmailStatus" class="form-select">
                    <option value="">Wszyscy (Email)</option>
                    <option value="verified">Potwierdzony Email</option>
                    <option value="unverified">Niepotwierdzony Email</option>
                </select>
            </div>
            <div class="col-md-3">
                <select wire:model.live="filterParishStatus" class="form-select">
                    <option value="">Wszyscy (Status Parafii)</option>
                    <option value="approved">Zatwierdzony Parafianin</option>
                    <option value="pending">Oczekujący na zatwierdzenie</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                {{-- Placeholder na przycisk eksportu lub dodania --}}
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-light">
                <tr>
                    {{-- Nagłówki z sortowaniem --}}
                    <th wire:click="sortBy('id')" style="cursor: pointer;">ID</th>

                    <th wire:click="sortBy('full_name')" style="cursor: pointer;">
                        Parafianin
                        @if($sortCol === 'full_name') <i
                        class="fa-solid fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i> @endif
                    </th>

                    <th wire:click="sortBy('name')" style="cursor: pointer;">
                        Login
                        @if($sortCol === 'name') <i
                        class="fa-solid fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i> @endif
                    </th>

                    <th wire:click="sortBy('email_verified_at')" style="cursor: pointer;">
                        Status Email
                        @if($sortCol === 'email_verified_at') <i
                        class="fa-solid fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i> @endif
                    </th>

                    <th wire:click="sortBy('is_user_verified')" style="cursor: pointer;">
                        Status Parafii
                        @if($sortCol === 'is_user_verified') <i
                        class="fa-solid fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i> @endif
                    </th>

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
                                        class="rounded-circle me-2 object-fit-cover" width="40" height="40" alt="Avatar">
                                @else
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2"
                                        style="width: 40px; height: 40px;">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold">{{ $user->full_name ?? '---' }}</div>
                                    <div class="small text-muted">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->name }}</td>
                        <td>
                            @if($user->email_verified_at)
                                <span class="badge text-bg-success"><i class="fa-solid fa-check"></i> OK</span>
                            @else
                                <span class="badge text-bg-warning">Brak</span>
                            @endif
                        </td>
                        <td>
                            <button wire:click="openApprovalModal({{ $user->id }})"
                                class="btn badge rounded-pill {{ $user->is_user_verified ? 'text-bg-success' : 'text-bg-danger' }} border-0">
                                {{ $user->is_user_verified ? 'Zatwierdzony' : 'Oczekuje' }}
                            </button>
                        </td>
                        <td class="text-end">
                            <button wire:click="openApprovalModal({{ $user->id }})" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-pen-to-square"></i> Edytuj
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-users-slash fa-2x mb-3"></i><br>
                            Brak parafian spełniających kryteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 border-top">
        {{ $users->links() }}
    </div>
</div>