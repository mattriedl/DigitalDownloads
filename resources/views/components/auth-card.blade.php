@props(['heading', 'intro' => null])
{{-- Shared frame for the sign-in pages: one narrow, centered column --}}
<div class="mx-auto max-w-md py-6">
    <div class="panel p-6 sm:p-8">
        <h1 class="font-display text-3xl font-bold">{{ $heading }}</h1>
        @if ($intro)<p class="mt-1 text-muted">{{ $intro }}</p>@endif

        @if (session('status'))
            <p class="mt-4 rounded-md border border-brand/40 bg-brand/10 px-3 py-2 text-sm" role="status">{{ session('status') }}</p>
        @endif

        {{ $slot }}
    </div>
    @isset($below)<p class="mt-4 text-center text-sm text-muted">{{ $below }}</p>@endisset
</div>
