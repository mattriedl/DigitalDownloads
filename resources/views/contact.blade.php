<x-layout title="Contact us" description="Questions, suggestions or a download you want us to add? Send us a message.">
    @include('partials.breadcrumbs', ['crumbs' => ['Contact' => null]])
    <h1 class="font-display text-4xl font-bold">Contact us</h1>
    <p class="mt-1 max-w-[60ch] text-muted">Questions, a broken link, or a download you'd like us to add? Send a message and we'll reply by email.</p>

    @if (session('status'))
        <p class="mt-5 rounded-md border border-brand/40 bg-brand/10 px-4 py-3" role="status">{{ session('status') }}</p>
    @endif

    <form method="post" action="{{ route('contact.store') }}" class="panel mt-6 max-w-2xl space-y-4 p-5" novalidate>
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" class="field" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')<p id="name-error" class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" class="field" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<p id="email-error" class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label for="subject" class="mb-1 block text-sm font-medium">Subject <span class="font-normal text-muted">(optional)</span></label>
            <input id="subject" name="subject" value="{{ old('subject') }}" maxlength="190" class="field">
        </div>
        <div>
            <label for="message" class="mb-1 block text-sm font-medium">Message</label>
            <textarea id="message" name="message" rows="6" required maxlength="5000" class="field" @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>
            @error('message')<p id="message-error" class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>
        <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
        <button class="rounded-md bg-brand px-5 py-2.5 font-semibold text-brand-ink">Send message</button>
    </form>
</x-layout>
