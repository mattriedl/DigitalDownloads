@php
    $r = 54;
    $circumference = round(2 * M_PI * $r, 2);
@endphp
<x-layout :title="'Downloading '.$item->title" :noindex="true" :sidebar="false" :category="$item->category_id">
    <div class="mx-auto max-w-5xl">
        <x-ad-slot zone="download_countdown_top" :category="$item->category_id" class="mb-6"/>

        <div class="grid items-center gap-6 md:grid-cols-[minmax(0,1fr)_336px]">
            <section class="panel p-6 text-center sm:p-10" data-countdown="{{ $seconds }}" aria-live="polite">
                <p class="text-sm text-muted">{{ $link->label }} for</p>
                <h1 class="mt-1 font-display text-3xl font-bold leading-tight">{{ $item->title }}
                    @if ($item->version)<span class="font-sans text-base font-medium text-muted">{{ $item->version }}</span>@endif
                </h1>

                <div class="relative mx-auto mt-8 size-40">
                    <svg viewBox="0 0 120 120" class="size-full -rotate-90" aria-hidden="true">
                        <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="var(--c-rule)" stroke-width="8"/>
                        <circle data-ring cx="60" cy="60" r="{{ $r }}" fill="none" stroke="var(--c-brand)" stroke-width="8" stroke-linecap="round"
                                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $circumference }}"
                                style="transition: stroke-dashoffset 1s linear"/>
                    </svg>
                    <div class="absolute inset-0 grid place-items-center">
                        <span data-waiting class="font-display text-6xl font-bold tabular-nums"><span data-seconds>{{ $seconds }}</span></span>
                        <span data-ready hidden class="text-brand"><x-ui-icon name="download" class="size-14"/></span>
                    </div>
                </div>

                <div data-waiting class="mt-6">
                    <p class="font-medium">Your download link will be ready in a few seconds.</p>
                    <p class="mt-1 text-sm text-muted">Keep this tab open.</p>
                </div>

                <div data-ready hidden class="mt-6">
                    <a href="{{ $goUrl }}" rel="nofollow" class="btn-download w-full sm:w-auto sm:min-w-72">
                        <x-ui-icon name="download" class="size-6"/>
                        {{ $link->type === 'hosted' ? 'Start download' : 'Go to download' }} {{ $link->file_size ?? $item->file_size ? '('.($link->file_size ?? $item->file_size).')' : '' }}
                    </a>
                    @if ($link->type === 'external')
                        <p class="mt-3 flex items-center justify-center gap-1.5 text-sm text-muted">
                            <x-ui-icon name="external" class="size-4"/> Opens {{ parse_url($link->url, PHP_URL_HOST) }}
                        </p>
                    @endif
                </div>

                <noscript>
                    <p class="mt-6"><a href="{{ $goUrl }}" rel="nofollow" class="btn-download">Go to download</a></p>
                </noscript>
            </section>

            <x-ad-slot zone="download_countdown_main" :category="$item->category_id"/>
        </div>

        @if ($links->count() > 1)
            <section class="mt-6 text-sm" aria-labelledby="other-links">
                <h2 id="other-links" class="font-semibold">Link not working? Try another</h2>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($links->where('id', '!=', $link->id) as $other)
                        <li><a href="{{ route('items.get', [$item, $other]) }}" rel="nofollow" class="btn-quiet">{{ $other->label }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        <p class="mt-6 text-center text-sm">
            <a href="{{ route('items.show', $item) }}" class="text-muted hover:text-ink">Back to {{ $item->title }}</a>
        </p>

        <x-ad-slot zone="download_countdown_bottom" :category="$item->category_id" class="mt-6"/>
    </div>
</x-layout>
