@php
    $width = $ad?->zone?->width ?? $zoneModel?->width;
    $height = $ad?->zone?->height ?? $zoneModel?->height;
    if ($ad?->type === \App\Models\Ad::TYPE_BANNERPROMOTE) {
        [$width, $height] = \App\Support\BannerPromote::size($ad->spot) ?? [$width, $height];
        $mobileSize = \App\Support\BannerPromote::size($ad->mobile_spot);
    }
@endphp
<aside {{ $attributes->merge(['class' => 'flex w-full justify-center '.$class]) }} aria-label="Advertisement">
    @if ($ad && $ad->type === \App\Models\Ad::TYPE_BANNERPROMOTE)
        {{--
            The spot name is set just before BannerPromote's script runs (see the layout), picking the
            phone spot on narrow screens so a 728px banner never overflows a phone.
        --}}
        <div class="max-w-full overflow-hidden"
             data-bp-spot="{{ $ad->spot }}"
             data-bp-height="{{ $height }}"
             @if ($ad->mobile_spot) data-bp-mobile-spot="{{ $ad->mobile_spot }}" data-bp-mobile-height="{{ $mobileSize[1] ?? $height }}" @endif
             style="min-height: {{ $height }}px"></div>
    @elseif ($ad)
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
