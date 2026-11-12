@php
    $popular = \Illuminate\Support\Facades\Cache::remember('sidebar_popular', now()->addMinutes(10), fn () => \App\Models\Item::published()
        ->popular()->take(10)->get(['id', 'title', 'slug', 'thumbnail', 'download_count', 'version']));
@endphp
@if ($popular->isNotEmpty())
    <section class="panel p-4" aria-labelledby="sidebar-popular">
        <h2 id="sidebar-popular" class="section-title text-lg">Most downloaded</h2>
        <ol class="mt-3 space-y-1">
            @foreach ($popular as $item)
                <li>
                    <a href="{{ route('items.show', $item) }}" class="group flex items-center gap-3 rounded px-1 py-1.5 hover:bg-raised">
                        <span class="w-5 shrink-0 text-right font-display text-lg font-bold text-muted">{{ $loop->iteration }}</span>
                        <x-item-thumb :item="$item" class="size-8"/>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium group-hover:text-brand">{{ $item->title }}</span>
                            <span class="block text-xs text-muted">{{ number_format($item->download_count) }} downloads</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    </section>
@endif
