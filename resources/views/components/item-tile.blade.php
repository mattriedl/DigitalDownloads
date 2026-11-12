@props(['item'])
{{-- Featured tile used on the homepage only, where pictures help people browse --}}
<article class="group relative flex flex-col overflow-hidden rounded-lg border border-rule bg-surface hover:border-brand">
    @if ($item->thumbnail)
        <img src="{{ asset('storage/'.$item->thumbnail) }}" alt="" loading="lazy" class="aspect-[16/10] w-full object-cover">
    @else
        <div class="grid aspect-[16/10] place-items-center" style="background: hsl({{ crc32($item->title) % 360 }} 40% 24%)">
            <x-item-thumb :item="$item" class="size-16 text-2xl ring-4 ring-black/10"/>
        </div>
    @endif
    <div class="flex flex-1 flex-col p-3">
        <h3 class="font-display text-base font-semibold leading-snug">
            <a href="{{ route('items.show', $item) }}" class="after:absolute after:inset-0 group-hover:text-brand">{{ $item->title }}</a>
        </h3>
        <p class="mt-auto flex justify-between gap-2 pt-2 text-xs text-muted"><span class="truncate">{{ $item->category?->name }}</span><span class="shrink-0">{{ number_format($item->download_count) }}</span></p>
    </div>
</article>
