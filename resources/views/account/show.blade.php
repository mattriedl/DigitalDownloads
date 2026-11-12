<x-layout title="My downloads" :noindex="true">
    <h1 class="font-display text-3xl font-bold">Hi, {{ auth()->user()->name }}</h1>
    @if (session('status'))
        <p class="mt-3 rounded-md border border-brand/40 bg-brand/10 px-4 py-3 text-sm" role="status">{{ session('status') }}</p>
    @endif
    @unless (auth()->user()->hasVerifiedEmail())
        <p class="mt-3 rounded-md border border-star/40 bg-star/10 px-4 py-3 text-sm">
            Confirm your email to write reviews and comments. <a href="{{ route('verification.notice') }}" class="underline">Send the link again</a>
        </p>
    @endunless

    <div class="mt-5">@include('account._nav')</div>

    <section aria-labelledby="saved">
        <h2 id="saved" class="section-title">Saved downloads</h2>
        <div class="panel mt-3 overflow-hidden">
            @forelse ($favorites as $item)
                <x-item-row :item="$item"/>
            @empty
                <p class="p-5 text-sm text-muted">Nothing saved yet. Use <span class="text-ink">Save</span> on any download page to keep it here.</p>
            @endforelse
        </div>
        {{ $favorites->links('partials.pagination') }}
    </section>

    <section class="mt-8" aria-labelledby="history">
        <h2 id="history" class="section-title">Recently downloaded</h2>
        @if ($history->isEmpty())
            <p class="mt-3 text-sm text-muted">Downloads you start while signed in will show up here.</p>
        @else
            <ol class="panel mt-3 divide-y divide-rule">
                @foreach ($history as $download)
                    <li class="flex items-center gap-3 px-4 py-3">
                        <x-item-thumb :item="$download->item" class="size-9"/>
                        <a href="{{ route('items.show', $download->item) }}" class="min-w-0 flex-1 truncate font-medium hover:text-brand">{{ $download->item->title }}</a>
                        <time class="shrink-0 text-xs text-muted" datetime="{{ $download->created_at->toIso8601String() }}">{{ $download->created_at->diffForHumans() }}</time>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</x-layout>
