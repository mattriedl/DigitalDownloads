{{-- Shared body for error pages that can still use the site layout (the database is fine). --}}
<x-layout :title="$heading" :noindex="true">
    <div class="panel p-8">
        <h1 class="font-display text-4xl font-bold">{{ $heading }}</h1>
        <p class="mt-2 max-w-[60ch] text-muted">{{ $text }}</p>
        <p class="mt-5 flex flex-wrap gap-3">
            <a href="{{ url('/') }}" class="btn-download py-2 text-base">Go to the homepage</a>
            @isset($action){!! $action !!}@endisset
        </p>
    </div>
</x-layout>
