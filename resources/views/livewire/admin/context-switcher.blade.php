<div class="d-inline-block" style="min-width: 200px;">
    @if($parishes->isNotEmpty())
        <select wire:model.live="currentParishId" class="form-select form-select-sm">
            <option value="" disabled>Wybierz parafię...</option>
            @foreach($parishes as $parish)
                <option value="{{ $parish->id }}">{{ $parish->name }}</option>
            @endforeach
        </select>
    @else
        <span class="text-danger small">Brak przypisanych parafii</span>
    @endif
</div>