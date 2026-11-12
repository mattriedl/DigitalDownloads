<x-layout :title="'Ticket #'.$ticket->id" :noindex="true">
    <h1 class="font-display text-3xl font-bold">Support</h1>
    <div class="mt-5">@include('account._nav')</div>

    @if (session('status'))
        <p class="mb-4 rounded-md border border-brand/40 bg-brand/10 px-4 py-3 text-sm" role="status">{{ session('status') }}</p>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-muted"><a href="{{ route('tickets.index') }}" class="hover:text-ink">All tickets</a> / #{{ $ticket->id }}</p>
            <h2 class="mt-1 font-display text-2xl font-semibold">{{ $ticket->subject }}</h2>
        </div>
        <div class="flex items-center gap-3">
            @include('account.tickets._status', ['status' => $ticket->status])
            @if ($ticket->status !== 'closed')
                <form method="post" action="{{ route('tickets.close', $ticket) }}">@csrf<button class="btn-quiet py-1.5">Close ticket</button></form>
            @endif
        </div>
    </div>

    <ol class="mt-5 space-y-3">
        @foreach ($ticket->messages as $message)
            <li class="panel p-4 {{ $message->is_staff ? 'border-brand/40' : '' }}">
                <p class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                    <span class="font-semibold">{{ $message->is_staff ? 'Support team' : ($message->user?->name ?? 'You') }}</span>
                    <time class="text-xs text-muted" datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, Y g:i a') }}</time>
                </p>
                <div class="mt-2 whitespace-pre-line text-[15px]">{{ $message->body }}</div>
                @if ($message->attachments->isNotEmpty())
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($message->attachments as $file)
                            <li><a href="{{ route('tickets.attachment', [$ticket, $file]) }}" class="btn-quiet py-1 text-xs">{{ $file->original_name }} <span class="text-muted">{{ \Illuminate\Support\Number::fileSize($file->size) }}</span></a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ol>

    <form method="post" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data" class="panel mt-5 space-y-4 p-5">
        @csrf
        <label for="reply" class="block font-semibold">{{ $ticket->status === 'closed' ? 'Reply to reopen this ticket' : 'Reply' }}</label>
        <textarea id="reply" name="message" rows="5" required maxlength="5000" class="field">{{ old('message') }}</textarea>
        @error('message')<p class="text-sm text-red-400">{{ $message }}</p>@enderror
        @include('account.tickets._files')
        <button class="rounded-md bg-brand px-4 py-2 font-semibold text-brand-ink">Send reply</button>
    </form>
</x-layout>
