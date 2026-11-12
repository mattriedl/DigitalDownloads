<x-layout title="Confirm your email" :noindex="true" :sidebar="false">
    <x-auth-card heading="Confirm your email" intro="We sent a link to {{ auth()->user()?->email }}. Open it to finish setting up your account, then you can write reviews and comments.">
        @if (session('status') === 'verification-link-sent')
            <p class="mt-4 rounded-md border border-brand/40 bg-brand/10 px-3 py-2 text-sm" role="status">A new link is on its way.</p>
        @endif
        <form method="post" action="{{ route('verification.send') }}" class="mt-5">
            @csrf
            <button class="w-full rounded-md bg-brand px-4 py-2.5 font-semibold text-brand-ink">Send the link again</button>
        </form>
        <x-slot:below><a href="{{ route('account') }}" class="text-ink underline">Go to my account</a></x-slot:below>
    </x-auth-card>
</x-layout>
