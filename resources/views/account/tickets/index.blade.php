<x-layout title="Support" :noindex="true">
    <h1 class="font-display text-3xl font-bold">Support</h1>
    <div class="mt-5">@include('account._nav')</div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Questions about a download or your account? Open a ticket and we'll reply here and by email.</p>
        <a href="{{ route('tickets.create') }}" class="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-brand-ink">New ticket</a>
    </div>

    <div class="panel mt-4 overflow-hidden">
        @forelse ($tickets as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="flex items-center gap-4 border-b border-rule px-4 py-3 last:border-b-0 hover:bg-raised/60">
                <span class="w-12 shrink-0 text-sm text-muted">#{{ $ticket->id }}</span>
                <span class="min-w-0 flex-1 truncate font-medium">{{ $ticket->subject }}</span>
                @include('account.tickets._status', ['status' => $ticket->status])
                <span class="hidden w-28 shrink-0 text-right text-xs text-muted sm:block">{{ $ticket->last_reply_at?->diffForHumans() }}</span>
            </a>
        @empty
            <p class="p-5 text-sm text-muted">You haven't opened any tickets.</p>
        @endforelse
    </div>
    {{ $tickets->links('partials.pagination') }}
</x-layout>
