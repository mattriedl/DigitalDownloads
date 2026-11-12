<x-layout title="Reset your password" :noindex="true" :sidebar="false">
    <x-auth-card heading="Reset your password" intro="Enter your email and we'll send you a link to choose a new password.">
        <form method="post" action="{{ route('password.email') }}" class="mt-5 space-y-4">
            @csrf
            <x-field name="email" label="Email" type="email" autocomplete="email" required autofocus/>
            <button class="w-full rounded-md bg-brand px-4 py-2.5 font-semibold text-brand-ink">Email me a reset link</button>
        </form>
        <x-slot:below><a href="{{ route('login') }}" class="text-ink underline">Back to sign in</a></x-slot:below>
    </x-auth-card>
</x-layout>
