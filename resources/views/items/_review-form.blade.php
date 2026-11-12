{{-- Write or update your review. Needs a signed-in, email-confirmed account. --}}
<div class="panel mt-4 p-4">
    @guest
        <p class="text-sm"><a href="{{ route('login') }}" class="font-semibold text-brand underline">Sign in</a> to rate and review {{ $item->title }}.</p>
    @else
        @if (! auth()->user()->hasVerifiedEmail())
            <p class="text-sm">Confirm your email to write a review. <a href="{{ route('verification.notice') }}" class="underline">Send the link again</a></p>
        @else
            <form method="post" action="{{ route('items.review', $item) }}" class="space-y-3">
                @csrf
                <h3 class="font-semibold">{{ $myReview ? 'Update your review' : 'Write a review' }}</h3>
                @if ($myReview && $myReview->status === 'pending')
                    <p class="text-xs text-muted">Your review is waiting to be checked.</p>
                @endif
                <fieldset>
                    <legend class="mb-1 text-sm">Your rating</legend>
                    {{-- Five radio buttons drawn as stars; picking one fills it and the ones before it --}}
                    <div class="flex flex-row-reverse justify-end gap-1 [&>input:checked~label]:text-star [&>label:hover]:text-star [&>label:hover~label]:text-star">
                        @for ($i = 5; $i >= 1; $i--)
                            <input type="radio" name="rating" value="{{ $i }}" id="rate-{{ $i }}" class="peer sr-only" @checked((int) old('rating', $myReview?->rating) === $i) required>
                            <label for="rate-{{ $i }}" class="cursor-pointer text-rule" title="{{ $i }} {{ \Illuminate\Support\Str::plural('star', $i) }}">
                                <svg class="size-7" viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M10 1.5l2.6 5.6 6.1.6-4.6 4.1 1.3 6-5.4-3.1-5.4 3.1 1.3-6L1.3 7.7l6.1-.6z"/></svg>
                                <span class="sr-only">{{ $i }} {{ \Illuminate\Support\Str::plural('star', $i) }}</span>
                            </label>
                        @endfor
                    </div>
                    @error('rating')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                </fieldset>
                <div>
                    <label for="review-title" class="sr-only">Title</label>
                    <input id="review-title" name="title" maxlength="120" value="{{ old('title', $myReview?->title) }}" placeholder="Sum it up (optional)" class="field">
                </div>
                <div>
                    <label for="review-body" class="sr-only">Review</label>
                    <textarea id="review-body" name="body" rows="3" maxlength="3000" placeholder="What worked, what didn't? (optional)" class="field">{{ old('body', $myReview?->body) }}</textarea>
                </div>
                <button class="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-brand-ink">{{ $myReview ? 'Update review' : 'Post review' }}</button>
            </form>
        @endif
    @endguest
</div>
