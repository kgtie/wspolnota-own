<div>
    <form wire:submit.prevent="save" class="row justify-content-center g-2">
        <div class="col-md-5">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-transparent border-end-0" style="border-color:#6b7280;"><i
                        class="bi bi-envelope"></i></span>
                <input type="email" class="form-control border-start-0 @error('email') is-invalid @enderror"
                    placeholder="Adres e-mail" wire:model.debounce.1000ms="email">
            </div>
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-gradient btn-lg w-100" wire:loading.attr="disabled">
                <span wire:loading.remove>Dołączam</span>
                <span wire:loading>Chwileczkę...</span>
            </button>
        </div>
        @error('email')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @if($statusMessage)
            <div class="alert alert-{{ $statusType == 'success' ? 'success' : 'warning' }} mt-3 fade show" role="alert">
                @if($statusType == 'success') <i class="fa-solid fa-check-circle me-2"></i> @endif
                {{ $statusMessage }}
            </div>
        @endif
    </form>
</div>