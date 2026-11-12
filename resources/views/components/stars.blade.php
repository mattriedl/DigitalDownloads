@props(['rating' => 0, 'count' => null, 'size' => 'size-4'])
@php $filled = (int) round((float) $rating); @endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
    <span class="inline-flex text-star" role="img" aria-label="{{ number_format((float) $rating, 1) }} out of 5 stars">
        @for ($i = 1; $i <= 5; $i++)
            <svg class="{{ $size }}" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.1 1.3 6-5.4-3.1-5.4 3.1 1.3-6L1.3 7.7l6.1-.6z"
                      stroke="currentColor" stroke-width="1" fill="{{ $filled >= $i ? 'currentColor' : 'none' }}"/>
            </svg>
        @endfor
    </span>
    @if ($count !== null)
        <span class="text-xs text-muted">({{ number_format($count) }})</span>
    @endif
</span>
