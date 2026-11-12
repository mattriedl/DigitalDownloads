<x-layout title="Sign in" :noindex="true" :sidebar="false">
    <x-auth-card heading="Sign in" intro="Save downloads, keep your history and write reviews.">
        @include('auth._google')
        <form method="post" action="{{ route('login') }}" class="mt-5 space-y-4">
            @csrf
            <x-field name="email" label="Email" type="email" autocomplete="email" required autofocus/>
            <div>
                <x-field name="password" label="Password" type="password" autocomplete="current-password" required/>
                <p class="mt-1 text-right text-sm"><a href="{{ route('password.request') }}" class="text-muted hover:text-ink">Forgot your password?</a></p>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="accent-[var(--c-brand)]"> Keep me signed in</label>
            <button class="w-full rounded-md bg-brand px-4 py-2.5 font-semibold text-brand-ink">Sign in</button>
        </form>
        @if (setting('registration_enabled', true))
            <x-slot:below>New here? <a href="{{ route('register') }}" class="text-ink underline">Create an account</a></x-slot:below>
        @endif
    </x-auth-card>
</x-layout>
