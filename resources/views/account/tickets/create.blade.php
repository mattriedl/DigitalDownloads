<x-layout title="New support ticket" :noindex="true">
    <h1 class="font-display text-3xl font-bold">Support</h1>
    <div class="mt-5">@include('account._nav')</div>

    <form method="post" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="panel max-w-2xl space-y-4 p-5">
        @csrf
        <h2 class="section-title">New ticket</h2>
        <x-field name="subject" label="Subject" required maxlength="190" placeholder="e.g. Download link for Photo Tool is broken"/>
        <div>
            <label for="priority" class="mb-1 block text-sm font-medium">How urgent is it?</label>
            <select id="priority" name="priority" class="field w-auto">
                @foreach (['low' => 'Not urgent', 'normal' => 'Normal', 'high' => 'Urgent'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="message" class="mb-1 block text-sm font-medium">Message</label>
            <textarea id="message" name="message" rows="7" required maxlength="5000" class="field">{{ old('message') }}</textarea>
            @error('message')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>
        @include('account.tickets._files')
        <div class="flex gap-2">
            <button class="rounded-md bg-brand px-4 py-2 font-semibold text-brand-ink">Open ticket</button>
            <a href="{{ route('tickets.index') }}" class="btn-quiet">Cancel</a>
        </div>
    </form>
</x-layout>
