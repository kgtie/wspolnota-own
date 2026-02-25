<x-app-layout :currentParish="$currentParish" :pageInfo="$pageInfo">
    <div class="space-y-6">
        @if ($currentSet)
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <header class="mb-4">
                    <h2 class="text-2xl font-semibold text-zinc-900">{{ $currentSet->title }}</h2>
                    <p class="text-sm text-zinc-500">
                        Obowiazuje: {{ $currentSet->effective_from?->format('d.m.Y') }}
                        @if($currentSet->effective_to)
                            - {{ $currentSet->effective_to->format('d.m.Y') }}
                        @endif
                    </p>
                    @if($currentSet->week_label)
                        <p class="mt-1 text-sm text-zinc-600">{{ $currentSet->week_label }}</p>
                    @endif
                </header>

                @if ($currentSet->lead)
                    <p class="mb-4 text-zinc-700">{{ $currentSet->lead }}</p>
                @endif

                @if ($currentSet->summary_ai)
                    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        <strong>Streszczenie:</strong> {{ $currentSet->summary_ai }}
                    </div>
                @endif

                <ol class="list-decimal space-y-3 pl-5 text-zinc-800">
                    @foreach ($currentSet->items as $item)
                        <li class="{{ $item->is_important ? 'font-semibold text-zinc-900' : '' }}">
                            @if ($item->title)
                                <span>{{ $item->title }}:</span>
                            @endif
                            {{ $item->content }}
                        </li>
                    @endforeach
                </ol>

                @if ($currentSet->footer_notes)
                    <p class="mt-5 border-t border-zinc-200 pt-4 text-sm text-zinc-600">{{ $currentSet->footer_notes }}</p>
                @endif
            </section>
        @else
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-zinc-900">Brak opublikowanych ogloszen</h2>
                <p class="mt-2 text-zinc-600">Po publikacji zestawu ogloszen pojawi sie on tutaj automatycznie.</p>
            </section>
        @endif

        @if ($publishedSets->count() > 1)
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-zinc-900">Archiwum ostatnich zestawow</h3>

                <div class="space-y-4">
                    @foreach ($publishedSets->skip(1) as $set)
                        <article class="rounded-lg border border-zinc-200 p-4">
                            <h4 class="font-medium text-zinc-900">{{ $set->title }}</h4>
                            <p class="text-sm text-zinc-500">
                                {{ $set->effective_from?->format('d.m.Y') }}
                                @if($set->effective_to)
                                    - {{ $set->effective_to->format('d.m.Y') }}
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-zinc-600">Liczba ogloszen: {{ $set->items->count() }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
