<x-admin-layout :pageInfo="$pageInfo">
    <div class="row">
        <div class="col-12">

            {{-- Komponent Tabeli z Mszami --}}
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header border-0">
                    <h3 class="card-title">
                        <i class="fa-solid fa-church me-2"></i> Kalendarz Intencji
                    </h3>
                    <div class="card-tools">
                        {{-- Przycisk wywołujący Modal Dodawania (Livewire) --}}
                        <button class="btn btn-primary btn-sm" onclick="Livewire.dispatch('create-mass')">
                            <i class="fa-solid fa-plus"></i> Dodaj nową mszę
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <livewire:admin.masses.masses-table />
                </div>
            </div>

        </div>
    </div>

    {{-- Komponenty Modalne (ukryte) --}}
    <livewire:admin.masses.mass-form-modal />
    <livewire:admin.masses.mass-attendance-modal />

</x-admin-layout>