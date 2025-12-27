<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                Nadchodzące Msze Święte
            </div>
        </x-slot>

        <x-slot name="description">
            Najbliższe zaplanowane msze w parafii
        </x-slot>

        @if($this->hasNoMasses())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <x-heroicon-o-calendar class="h-12 w-12 text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    Brak zaplanowanych mszy
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                    Nie ma jeszcze żadnych mszy świętych zaplanowanych w tej parafii.
                    Użyj modułu "Msze święte" aby dodać intencje mszalne.
                </p>
                <x-filament::button class="mt-4" icon="heroicon-o-plus" tag="a" href="#" {{--
                    href="{{ route('filament.admin.resources.masses.create') }}" --}}>
                    Dodaj mszę
                </x-filament::button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->getUpcomingMasses() as $mass)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex flex-col items-center justify-center w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg">
                                <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
                                    {{ \Carbon\Carbon::parse($mass['start_time'])->format('D') }}
                                </span>
                                <span class="text-lg font-bold text-primary-700 dark:text-primary-300">
                                    {{ \Carbon\Carbon::parse($mass['start_time'])->format('d') }}
                                </span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $mass['intention'] ?? 'Msza św.' }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($mass['start_time'])->format('H:i') }}
                                    @if(isset($mass['location']))
                                        • {{ $mass['location'] }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            @if(isset($mass['attendees_count']) && $mass['attendees_count'] > 0)
                                <span class="inline-flex items-center gap-1 text-sm text-gray-500">
                                    <x-heroicon-o-users class="h-4 w-4" />
                                    {{ $mass['attendees_count'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>