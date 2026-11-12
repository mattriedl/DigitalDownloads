@php $user = auth()->user(); @endphp
<x-layout title="Account settings" :noindex="true">
    <h1 class="font-display text-3xl font-bold">Account settings</h1>
    <div class="mt-5">@include('account._nav')</div>

    @if (session('status') === 'profile-information-updated')
        <p class="mb-4 rounded-md border border-brand/40 bg-brand/10 px-4 py-3 text-sm" role="status">Profile saved.</p>
    @elseif (session('status') === 'password-updated')
        <p class="mb-4 rounded-md border border-brand/40 bg-brand/10 px-4 py-3 text-sm" role="status">Password saved.</p>
    @endif

    <div class="max-w-xl space-y-6">
        <section class="panel p-5" aria-labelledby="profile">
            <h2 id="profile" class="section-title">Profile</h2>
            <form method="post" action="{{ route('user-profile-information.update') }}" class="mt-4 space-y-4">
                @csrf @method('PUT')
                <x-field name="name" label="Name" :value="$user->name" bag="updateProfileInformation" autocomplete="name" required/>
                <x-field name="email" label="Email" type="email" :value="$user->email" bag="updateProfileInformation" autocomplete="email" required
                         help="Changing it sends a confirmation link to the new address."/>
                <button class="rounded-md bg-brand px-4 py-2 font-semibold text-brand-ink">Save profile</button>
            </form>
        </section>

        <section class="panel p-5" aria-labelledby="password">
            <h2 id="password" class="section-title">{{ $user->password ? 'Change password' : 'Set a password' }}</h2>
            @unless ($user->password)
                <p class="mt-1 text-sm text-muted">You sign in with Google. Set a password if you also want to sign in with your email.</p>
            @endunless
            <form method="post" action="{{ route('user-password.update') }}" class="mt-4 space-y-4">
                @csrf @method('PUT')
                @if ($user->password)
                    <x-field name="current_password" label="Current password" type="password" bag="updatePassword" autocomplete="current-password" required/>
                @endif
                <x-field name="password" label="New password" type="password" bag="updatePassword" autocomplete="new-password" required help="At least 10 characters."/>
                <x-field name="password_confirmation" label="Confirm new password" type="password" bag="updatePassword" autocomplete="new-password" required/>
                <button class="rounded-md bg-brand px-4 py-2 font-semibold text-brand-ink">Save password</button>
            </form>
        </section>

        <section class="panel border-red-500/30 p-5" aria-labelledby="delete">
            <h2 id="delete" class="section-title">Delete account</h2>
            <p class="mt-1 text-sm text-muted">Removes your account, saved downloads, reviews and comments. This can't be undone.</p>
            @if ($user->isStaff())
                <p class="mt-3 text-sm">Staff accounts are removed by another admin in the admin panel.</p>
            @else
                <form method="post" action="{{ route('account.destroy') }}" class="mt-4 space-y-4">
                    @csrf @method('DELETE')
                    @if ($user->password)
                        <x-field name="password" id="delete-password" label="Enter your password to confirm" type="password" bag="deleteAccount" autocomplete="current-password" required/>
                    @endif
                    <button class="rounded-md border border-red-500/60 px-4 py-2 font-semibold text-red-400 hover:bg-red-500/10">Delete my account</button>
                </form>
            @endif
        </section>
    </div>
</x-layout>
