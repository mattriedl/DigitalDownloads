@guest
    <p class="panel p-4 text-sm"><a href="{{ route('login') }}" class="font-semibold text-brand underline">Sign in</a> to join the conversation.</p>
@else
    @if (! auth()->user()->hasVerifiedEmail())
        <p class="panel p-4 text-sm">Confirm your email to comment. <a href="{{ route('verification.notice') }}" class="underline">Send the link again</a></p>
    @else
        <form method="post" action="{{ route('items.comment', $item) }}" class="panel space-y-3 p-4">
            @csrf
            <label for="comment-body" class="block text-sm font-semibold">Add a comment</label>
            <textarea id="comment-body" name="body" rows="3" maxlength="2000" required class="field" placeholder="Questions, tips, or how it went">{{ old('body') }}</textarea>
            @error('body')<p class="text-sm text-red-400">{{ $message }}</p>@enderror
            <button class="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-brand-ink">Post comment</button>
        </form>
    @endif
@endguest
