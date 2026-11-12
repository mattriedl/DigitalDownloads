<x-layout :title="$q ? 'Search: '.$q : 'Search'" :noindex="true">
    <h1 class="font-display text-3xl font-bold">
        @if ($q)Results for “{{ $q }}”@else Search downloads @endif
    </h1>

    <form action="{{ route('search') }}" method="get" role="search" class="mt-4 flex gap-2">
        <label for="search-page-q" class="sr-only">Search downloads</label>
        <input id="search-page-q" name="q" type="search" value="{{ $q }}" class="field" placeholder="App, game, template or publisher" autofocus>
        <button class="btn-download py-2 text-base">Search</button>
    </form>

    <x-ad-slot zone="category_top" class="mt-5"/>

    @if ($items)
        <p class="mt-5 text-sm text-muted">{{ number_format($items->total()) }} {{ \Illuminate\Support\Str::plural('result', $items->total()) }}</p>
        <div class="panel mt-2 overflow-hidden">
            @forelse ($items as $item)
                <x-item-row :item="$item"/>
            @empty
                <div class="p-6">
                    <p class="font-medium">No downloads match “{{ $q }}”.</p>
                    <p class="mt-1 text-sm text-muted">Try fewer or different words, or <a href="{{ route('categories.index') }}" class="text-brand underline">browse the categories</a>.</p>
                </div>
            @endforelse
        </div>
        {{ $items->links('partials.pagination') }}
    @endif
</x-layout>
