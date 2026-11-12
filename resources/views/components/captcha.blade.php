{{-- Spam check widget, shown only when a captcha provider is set up in Site settings --}}
@if (\App\Support\Captcha::enabled())
    @php $provider = \App\Support\Captcha::provider(); @endphp
    <div>
        <div class="{{ $provider['class'] }}" data-sitekey="{{ setting('captcha_site_key') }}"
             data-theme="{{ setting('theme', 'dark') === 'light' ? 'light' : 'dark' }}"></div>
        @error($provider['field'])<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    @once
        <script src="{{ $provider['script'] }}" async defer></script>
    @endonce
@endif
