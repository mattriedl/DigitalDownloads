@php
    [$label, $classes] = match ($status) {
        'answered' => ['Answered', 'border-brand/50 text-brand'],
        'customer_reply' => ['Waiting for us', 'border-star/50 text-star'],
        'closed' => ['Closed', 'border-rule text-muted'],
        default => ['Open', 'border-star/50 text-star'],
    };
@endphp
<span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $classes }}">{{ $label }}</span>
