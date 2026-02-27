<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Kontrola pokrycia mszami (7 dni)
        </x-slot>

        <x-slot name="description">
            Sprawdzenie, czy na kazdy nadchodzacy dzien zaplanowano przynajmniej jedna msze swieta.
        </x-slot>

        @if ($isComplete)
            <div class="rounded-lg border border-success-200 bg-success-50 p-4 text-success-800">
                <p class="text-sm font-semibold">Plan mszalny na najblizszy tydzien jest kompletny.</p>
                <p class="mt-1 text-xs">
                    Pokrycie: {{ $coveredDays }}/{{ $daysTotal }} dni.
                </p>

                @if ($massesIndexUrl)
                    <div class="mt-3">
                        <x-filament::button
                            tag="a"
                            size="sm"
                            color="success"
                            :href="$massesIndexUrl"
                        >
                            Przejdz do listy mszy
                        </x-filament::button>
                    </div>
                @endif
            </div>
        @else
            @php
                $isDanger = ($severity ?? 'warning') === 'danger';
                $boxClass = $isDanger
                    ? 'rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-800'
                    : 'rounded-lg border border-warning-200 bg-warning-50 p-4 text-warning-800';
                $primaryButtonColor = $isDanger ? 'danger' : 'warning';
            @endphp
            <div class="{{ $boxClass }}">
                <p class="text-sm font-semibold">
                    Brakuje mszy na czesc dni w nadchodzacym tygodniu. Uzupelnij harmonogram.
                </p>
                <p class="mt-1 text-xs">
                    Pokrycie: {{ $coveredDays }}/{{ $daysTotal }} dni.
                </p>

                @if (! empty($missingDays))
                    <ul class="mt-3 list-inside list-disc text-xs">
                        @foreach ($missingDays as $day)
                            <li>{{ $day['label'] }}, {{ $day['date'] }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($massesCreateUrl)
                        <x-filament::button
                            tag="a"
                            size="sm"
                            :color="$primaryButtonColor"
                            :href="$massesCreateUrl"
                        >
                            Dodaj msze
                        </x-filament::button>
                    @endif

                    @if ($massesIndexUrl)
                        <x-filament::button
                            tag="a"
                            size="sm"
                            color="gray"
                            :href="$massesIndexUrl"
                        >
                            Otworz pelna liste
                        </x-filament::button>
                    @endif
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
