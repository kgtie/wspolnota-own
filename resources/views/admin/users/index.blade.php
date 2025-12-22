<x-admin-layout :pageInfo="$pageInfo">
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header border-0">
                    <h3 class="card-title">
                        <i class="fa-solid fa-users me-2"></i> Zarejestrowani parafianie
                    </h3>
                    <div class="card-tools">
                        TEST
                    </div>
                </div>

                <div class="card-body p-0">
                    <livewire:admin.users.users-table />
                </div>
            </div>

        </div>
    </div>

    {{-- Komponent Modala do Weryfikacji i Edycji (ukryty domyślnie, sterowany zdarzeniami) --}}
    <livewire:admin.users.user-approval-modal />

</x-admin-layout>