<div class="card p-4">
    <div class="mb-3">
        <label class="form-label">Imię i nazwisko</label>
        <input type="text" class="form-control" wire:model.debounce.500ms="full_name">
    </div>

    <div class="mb-3">
        <label class="form-label">Nazwa wyświetlana</label>
        <input type="text" class="form-control" wire:model.debounce.500ms="name">
    </div>

    <div class="mb-3">
        <label class="form-label">Avatar</label>
        <input type="file" class="form-control" wire:model="avatar">

        <div wire:loading wire:target="avatar" class="text-muted mt-1">
            Przesyłanie...
        </div>
    </div>
</div>