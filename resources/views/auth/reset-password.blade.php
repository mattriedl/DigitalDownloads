<x-layout title="Choose a new password" :noindex="true" :sidebar="false">
    <x-auth-card heading="Choose a new password">
        <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-field name="email" label="Email" type="email" :value="$request->email" autocomplete="email" required/>
            <x-field name="password" label="New password" type="password" autocomplete="new-password" required help="At least 10 characters." autofocus/>
            <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required/>
            <button class="w-full rounded-md bg-brand px-4 py-2.5 font-semibold text-brand-ink">Save new password</button>
        </form>
    </x-auth-card>
</x-layout>
