{{-- $crumbs: [label => url|null]; the last one is the current page --}}
@php
    $trail = ['Home' => route('home')] + $crumbs;
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($trail)->keys()->values()->map(fn ($label, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $label,
            'item' => $trail[$label] ?: url()->current(),
        ])->all(),
    ];
@endphp
<nav aria-label="Breadcrumb" class="mb-3 text-sm text-muted">
    <ol class="flex flex-wrap items-center gap-1">
        <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
        @foreach ($crumbs as $label => $url)
            <li class="flex items-center gap-1">
                <x-ui-icon name="chevron-right" class="size-3.5"/>
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-ink">{{ $label }}</a>
                @else
                    <span aria-current="page" class="text-ink">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
