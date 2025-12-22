<div>
    <div class="modal fade" id="userApprovalModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            @if($user)
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edycja: <strong>{{ $full_name ?? $name }}</strong></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    
                    {{-- Komunikaty --}}
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Zakładki (Tabs) --}}
                    <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">Dane Profilowe</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button" role="tab">Status i Weryfikacja</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="userTabsContent">
                        
                        {{-- TAB 1: Dane Profilowe --}}
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <form wire:submit.prevent="saveChanges">
                                <div class="row">
                                    <div class="col-md-4 text-center mb-3">
                                        <label class="form-label fw-bold d-block">Avatar</label>
                                        
                                        @if ($newAvatar) 
                                            {{-- Podgląd nowego uploadu --}}
                                            <img src="{{ $newAvatar->temporaryUrl() }}" class="img-thumbnail rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                                        @elseif($user->avatar)
                                            {{-- Aktualny avatar --}}
                                            <img src="{{ Storage::disk('profiles')->url($user->avatar) }}" class="img-thumbnail rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                                        @else
                                            {{-- Placeholder --}}
                                            <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 120px; height: 120px; font-size: 2rem;">
                                                {{ substr($name, 0, 1) }}
                                            </div>
                                        @endif

                                        <div class="d-grid gap-2">
                                            <label class="btn btn-sm btn-outline-primary cursor-pointer">
                                                <i class="fa-solid fa-camera"></i> Zmień
                                                <input type="file" wire:model="newAvatar" class="d-none">
                                            </label>
                                            @if($user->avatar && !$newAvatar)
                                                <button type="button" wire:click="deleteAvatar" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i> Usuń
                                                </button>
                                            @endif
                                        </div>
                                        @error('newAvatar') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label class="form-label">Imię i Nazwisko</label>
                                            <input type="text" class="form-control" wire:model="full_name">
                                            @error('full_name') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Login (Nazwa)</label>
                                            <input type="text" class="form-control" wire:model="name">
                                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Adres E-mail</label>
                                            <input type="email" class="form-control" wire:model="email">
                                            @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="button" wire:click="sendPasswordReset" class="btn btn-outline-warning">
                                        <i class="fa-solid fa-key"></i> Wyślij reset hasła
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-save"></i> Zapisz zmiany
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- TAB 2: Status i Weryfikacja (To co było wcześniej) --}}
                        <div class="tab-pane fade" id="status" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-12 text-center mb-4">
                                    <h6 class="text-muted text-uppercase small">Kod weryfikacyjny (9 cyfr)</h6>
                                    <div class="display-6 font-monospace text-primary fw-bold">
                                        {{ chunk_split($verificationCode, 3, ' ') }}
                                    </div>
                                    <button wire:click="generateNewCode" class="btn btn-link btn-sm text-decoration-none">
                                        Generuj nowy kod
                                    </button>
                                </div>

                                <div class="col-md-6 offset-md-3">
                                    <div class="d-grid gap-3">
                                        @if(!$is_user_verified)
                                            <div class="alert alert-warning text-center small mb-0">
                                                Użytkownik nie jest jeszcze zatwierdzonym parafianinem.
                                            </div>
                                            <button wire:click="approveUser" class="btn btn-success">
                                                <i class="fa-solid fa-check"></i> Zatwierdź Parafianina
                                            </button>
                                        @else
                                            <div class="alert alert-success text-center small mb-0">
                                                Użytkownik jest zatwierdzonym parafianinem.
                                            </div>
                                            <button wire:click="revokeApproval" class="btn btn-outline-danger">
                                                <i class="fa-solid fa-ban"></i> Cofnij zatwierdzenie
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div> {{-- End Tab Content --}}

                </div>
            </div>
            @endif
        </div>
    </div>
    {{-- Skrypt JS do obsługi otwierania/zamykania Modala --}}
    <script>
        document.addEventListener('livewire:init', () => {
            // Nasłuchiwanie na otwarcie
            Livewire.on('open-bootstrap-modal', () => {
                const modalEl = document.getElementById('userApprovalModal');
                // Używamy getOrCreateInstance, aby zawsze pobrać aktualną instancję dla aktualnego elementu DOM
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            });

            // Nasłuchiwanie na zamknięcie
            Livewire.on('close-bootstrap-modal', () => {
                const modalEl = document.getElementById('userApprovalModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
            });

            // Opcjonalnie: Czyszczenie tła (backdrop) po zamknięciu, 
            // czasami Bootstrap gubi się przy dynamicznych zmianach DOM
            const modalEl = document.getElementById('userApprovalModal');
            modalEl.addEventListener('hidden.bs.modal', () => {
                // Jeśli livewire coś namiesza, to usuwamy ręcznie backdropy
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            });
        });
    </script>
</div>