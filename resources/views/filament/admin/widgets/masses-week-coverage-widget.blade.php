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
            </div>
        @else
            <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-800">
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
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
