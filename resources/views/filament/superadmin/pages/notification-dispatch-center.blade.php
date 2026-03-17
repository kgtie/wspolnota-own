<x-filament-panels::page>
    <div class="grid gap-6" wire:poll.30s>
        <x-filament::section
            heading="Operacyjny status dispatchu"
            description="Gotowosc, wysylki i biezace problemy dla news, ogloszen oraz przypomnien mszalnych."
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($this->dispatchCards as $card)
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format($card['value'], 0, ',', ' ') }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-filament::section
                heading="News dispatch"
                description="Ostatnie opublikowane aktualnosci i status wysylki."
            >
                <div class="space-y-3">
                    @forelse ($this->recentNews as $row)
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-950 dark:text-white">{{ $row['title'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $row['parish'] }} · opublikowano {{ $row['published_at'] }}</p>
                                </div>
                                <span class="text-xs text-gray-500">#{{ $row['id'] }}</span>
                            </div>
                            <div class="mt-3 grid gap-2 md:grid-cols-2">
                                <div class="rounded-xl bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800/80">
                                    <span class="font-medium">Push:</span> {{ $row['push_sent_at'] }}
                                </div>
                                <div class="rounded-xl bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800/80">
                                    <span class="font-medium">Email:</span> {{ $row['email_sent_at'] }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Brak opublikowanych newsow.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section
                heading="Ogloszenia dispatch"
                description="Ostatnie zestawy ogloszen i status wysylki."
            >
                <div class="space-y-3">
                    @forelse ($this->recentAnnouncementSets as $row)
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-950 dark:text-white">{{ $row['title'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $row['parish'] }} · opublikowano {{ $row['published_at'] }}</p>
                                </div>
                                <span class="text-xs text-gray-500">#{{ $row['id'] }}</span>
                            </div>
                            <div class="mt-3 grid gap-2 md:grid-cols-2">
                                <div class="rounded-xl bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800/80">
                                    <span class="font-medium">Push:</span> {{ $row['push_sent_at'] }}
                                </div>
                                <div class="rounded-xl bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800/80">
                                    <span class="font-medium">Email:</span> {{ $row['email_sent_at'] }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Brak opublikowanych ogloszen.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-filament::section
                heading="Przypomnienia mszalne"
                description="Najblizsze msze z postepem push 24h/8h/1h oraz porannego emaila."
            >
                <div class="space-y-3">
                    @forelse ($this->upcomingMasses as $row)
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-950 dark:text-white">{{ $row['title'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $row['parish'] }} · {{ $row['celebration_at'] }}</p>
                                </div>
                                <span class="text-xs text-gray-500">#{{ $row['id'] }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-800">uczestnicy {{ $row['participants'] }}</span>
                                <span class="rounded-full bg-sky-100 px-3 py-1 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">24h {{ $row['push_24h'] }}</span>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">8h {{ $row['push_8h'] }}</span>
                                <span class="rounded-full bg-rose-100 px-3 py-1 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">1h {{ $row['push_1h'] }}</span>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">email {{ $row['email'] }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Brak nadchodzacych mszy w horyzoncie 3 dni.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section
                heading="Failed mail jobs"
                description="Retry i czyszczenie nieudanych jobow mailowych per typ notyfikacji."
            >
                <div class="space-y-3">
                    @forelse ($this->failedMailJobs as $row)
                        <div class="rounded-2xl border border-red-200 bg-white p-4 dark:border-red-900 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-950 dark:text-white">{{ $row['type'] }}</p>
                                    <p class="text-sm text-gray-500">job #{{ $row['id'] }} · queue {{ $row['queue'] }} · {{ $row['failed_at'] }}</p>
                                    <p class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $row['exception_headline'] }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <x-filament::button size="sm" color="warning" wire:click="retryFailedJob({{ $row['id'] }})">
                                        Retry
                                    </x-filament::button>
                                    <x-filament::button size="sm" color="gray" wire:click="forgetFailedJob({{ $row['id'] }})">
                                        Forget
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Brak nieudanych mail jobs.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
