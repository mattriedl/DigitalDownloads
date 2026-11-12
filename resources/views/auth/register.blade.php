<x-layout title="Create an account" :noindex="true" :sidebar="false">
    <x-auth-card heading="Create an account" intro="Free. Save downloads, see your history and write reviews.">
        @include('auth._google')
        <form method="post" action="{{ route('register') }}" class="mt-5 space-y-4">
            @csrf
            <x-field name="name" label="Name" autocomplete="name" required maxlength="255" autofocus/>
            <x-field name="email" label="Email" type="email" autocomplete="email" required/>
            <x-field name="password" label="Password" type="password" autocomplete="new-password" required help="At least 10 characters."/>
            <x-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required/>
            <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="terms" value="1" required class="mt-1 accent-[var(--c-brand)]" @checked(old('terms'))>
                    <span>I agree to the <a href="{{ route('pages.show', 'terms-of-service') }}" class="underline" target="_blank">terms</a> and <a href="{{ route('pages.show', 'privacy-policy') }}" class="underline" target="_blank">privacy policy</a>.</span>
                </label>
                @error('terms')<p class="mt-1 text-sm text-red-400">Please accept the terms to create an account.</p>@enderror
            </div>
            <button class="w-full rounded-md bg-brand px-4 py-2.5 font-semibold text-brand-ink">Create account</button>
        </form>
        <x-slot:below>Already have an account? <a href="{{ route('login') }}" class="text-ink underline">Sign in</a></x-slot:below>
    </x-auth-card>
</x-layout>
