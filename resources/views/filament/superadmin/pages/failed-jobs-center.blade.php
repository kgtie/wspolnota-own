<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-1">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Centrum nieudanych zadań</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Globalny podgląd nieudanych zadań kolejki z szybkim ponowieniem, usunięciem wpisu i czyszczeniem kolejki błędów.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="space-y-1">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Kolejka</span>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="queueFilter">
                                <option value="">Wszystkie kolejki</option>
                                @foreach ($this->queueOptions as $queue => $label)
                                    <option value="{{ $queue }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>

                    <label class="space-y-1 sm:col-span-1 lg:col-span-2">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Szukaj</span>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="nazwa zadania, wyjątek, kolejka, typ"
                            />
                        </x-filament::input.wrapper>
                    </label>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @forelse ($this->failedJobStats as $card)
                <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format((int) $card['value']) }}</p>
                </article>
            @empty
                <article class="rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 md:col-span-2 xl:col-span-4">
                    Brak tabeli <code>failed_jobs</code> albo brak danych do pokazania.
                </article>
            @endforelse
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950/60">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Typ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nazwa zadania</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Kolejka</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Połączenie</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Błąd</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Czas błędu</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($this->failedJobs as $job)
                            <tr wire:key="failed-job-{{ $job['id'] }}">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">#{{ $job['id'] }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @php
                                        $kindTone = match ($job['kind']) {
                                            'mail' => 'primary',
                                            'push' => 'info',
                                            default => 'gray',
                                        };
                                    @endphp

                                    <x-filament::badge :color="$kindTone">
                                        {{ strtoupper($job['kind']) }}
                                    </x-filament::badge>
                                </td>
                                <td class="max-w-md px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $job['display_name'] }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $job['queue'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $job['connection'] }}</td>
                                <td class="max-w-lg px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $job['exception_headline'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $job['failed_at'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <x-filament::button size="sm" color="warning" wire:click="retryFailedJob({{ $job['id'] }})">
                                            Ponów
                                        </x-filament::button>
                                        <x-filament::button size="sm" color="gray" wire:click="forgetFailedJob({{ $job['id'] }})">
                                            Usuń wpis
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Brak nieudanych zadań dla aktualnych filtrów.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
