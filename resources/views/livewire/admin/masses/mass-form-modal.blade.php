<div>
    <div class="modal fade" id="massFormModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit.prevent="save">
                    <div class="modal-header {{ $isEdit ? 'bg-primary text-white' : 'bg-success text-white' }}">
                        <h5 class="modal-title">
                            @if($isEdit) <i class="fa-solid fa-pen me-2"></i> Edycja Mszy
                            @else <i class="fa-solid fa-plus me-2"></i> Nowa Msza @endif
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                        <div class="row g-3">
                            {{-- Data i Czas --}}
                            <div class="col-md-6">
                                <label class="form-label">Data</label>
                                <input type="date" wire:model="date" class="form-control" required>
                                @error('date') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Godzina</label>
                                <input type="time" wire:model="time" class="form-control" required>
                                @error('time') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Intencja --}}
                            <div class="col-12">
                                <label class="form-label fw-bold">Intencja Mszalna</label>
                                <textarea wire:model="intention" class="form-control" rows="3"
                                    placeholder="np. Za śp. Jana Kowalskiego w 5. rocznicę śmierci"></textarea>
                                @error('intention') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Szczegóły --}}
                            <div class="col-md-6">
                                <label class="form-label">Miejsce</label>
                                <input type="text" wire:model="location" class="form-control" list="locationsList">
                                <datalist id="locationsList">
                                    <option value="Kościół główny">
                                    <option value="Kaplica boczna">
                                    <option value="Kościół dolny">
                                    <option value="Cmentarz">
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ksiądz (Celebrans)</label>
                                <input type="text" wire:model="celebrant" class="form-control"
                                    placeholder="np. ks. Proboszcz">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Rodzaj mszy</label>
                                <select wire:model="type" class="form-select">
                                    <option value="z dnia">Z dnia (Zwykła)</option>
                                    <option value="Niedzielna">Niedzielna</option>
                                    <option value="Uroczystość">Uroczystość</option>
                                    <option value="Pogrzebowa">Pogrzebowa</option>
                                    <option value="Ślubna">Ślubna</option>
                                    <option value="Chrzcielna">Chrzcielna</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Typ rytu</label>
                                <select wire:model="rite" class="form-select">
                                    <option value="rzymski">Rzymski</option>
                                    <option value="trydencki">Trydencki</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <hr>
                            </div>

                            {{-- Stypendium --}}
                            <div class="col-md-6">
                                <label class="form-label text-success"><i class="fa-solid fa-coins"></i> Ofiara
                                    (Stypendium)</label>
                                <div class="input-group">
                                    <input type="number" wire:model="stipend" step="0.01" class="form-control"
                                        placeholder="0.00">
                                    <span class="input-group-text">PLN</span>
                                </div>
                                <div class="form-text">Informacja widoczna tylko dla Administratora.</div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn {{ $isEdit ? 'btn-primary' : 'btn-success' }}">
                            <i class="fa-solid fa-save"></i> {{ $isEdit ? 'Zapisz zmiany' : 'Dodaj mszę' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            const modalEl = document.getElementById('massFormModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            Livewire.on('open-mass-modal', () => modal.show());
            Livewire.on('close-mass-modal', () => modal.hide());
        });
    </script>
</div>