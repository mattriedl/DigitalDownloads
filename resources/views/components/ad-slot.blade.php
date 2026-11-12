@php
    $width = $ad?->zone?->width ?? $zoneModel?->width;
    $height = $ad?->zone?->height ?? $zoneModel?->height;
@endphp
<aside {{ $attributes->merge(['class' => 'flex w-full justify-center '.$class]) }} aria-label="Advertisement">
    @if ($ad)
        <div class="relative max-w-full overflow-hidden" @if ($height) style="min-height: {{ $height }}px" @endif>
            @if ($ad->type === \App\Models\Ad::TYPE_IMAGE)
                <a href="{{ route('ads.click', $ad) }}" rel="sponsored noopener" target="_blank">
                    <img src="{{ asset('storage/'.$ad->image_path) }}" alt="{{ $ad->name }}"
                         @if ($width) width="{{ $width }}" @endif @if ($height) height="{{ $height }}" @endif
                         loading="lazy" class="mx-auto h-auto max-w-full rounded">
                </a>
            @else
                {!! $ad->code !!}
            @endif
        </div>
    @else
        {{-- Placeholder shown only outside production, so the layout can be checked before ads exist --}}
        <div class="flex max-w-full items-center justify-center rounded border border-dashed border-rule bg-raised/60 text-center text-xs text-muted"
             style="width: {{ $width ?? 300 }}px; height: {{ $height ?? 250 }}px">
            Ad slot: {{ $zoneModel?->name ?? $zone }}<br>{{ $width }}×{{ $height }}
        </div>
    @endif
</aside>
