@props(['item', 'rank' => null, 'compact' => false])
{{-- One catalog row: the main way items are listed across the site --}}
<article class="group relative flex gap-4 border-b border-rule px-4 py-4 last:border-b-0 hover:bg-raised/60">
    @if ($rank)
        <span class="w-6 shrink-0 self-center text-right font-display text-2xl font-bold text-muted">{{ $rank }}</span>
    @endif
    <x-item-thumb :item="$item" class="size-14"/>
    <div class="min-w-0 flex-1">
        <h3 class="font-display text-lg font-semibold leading-snug">
            <a href="{{ route('items.show', $item) }}" class="after:absolute after:inset-0 group-hover:text-brand">{{ $item->title }}</a>
            @if ($item->version)<span class="ml-1 align-middle font-sans text-xs font-medium text-muted">{{ $item->version }}</span>@endif
        </h3>
        @if ($item->short_description)
            <p class="mt-0.5 line-clamp-2 text-sm text-muted">{{ $item->short_description }}</p>
        @endif
        <p class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted">
            @if ($item->relationLoaded('category') && $item->category)<span>{{ $item->category->name }}</span>@endif
            @if ($item->license)<span>{{ $item->license }}</span>@endif
            @if ($item->file_size)<span>{{ $item->file_size }}</span>@endif
            <span>{{ number_format($item->download_count) }} downloads</span>
            @if ($item->rating_count)<x-stars :rating="$item->rating_avg" size="size-3.5"/>@endif
        </p>
    </div>
    @unless ($compact)
    <span class="hidden shrink-0 self-center rounded-md border border-rule px-3 py-1.5 text-sm font-semibold text-brand group-hover:border-brand sm:inline-flex">Download</span>
    @endunless
</article>
