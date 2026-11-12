@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'image' => null,
    'noindex' => false,
    'category' => null,
    'sidebar' => true,
])
@php
    $siteName = setting('site_name', config('app.name'));
    $suffix = setting('seo_title_suffix') ?: ' | '.$siteName;
    $fullTitle = $title ? \Illuminate\Support\Str::limit($title, 70, '').$suffix : (setting('seo_home_title') ?: $siteName);
    $metaDescription = \Illuminate\Support\Str::limit(strip_tags((string) ($description ?: setting('seo_home_description') ?: setting('site_tagline'))), 160, '');
    $ogImage = $image ?: (setting('og_image') ? asset('storage/'.setting('og_image')) : null);
    $theme = setting('theme', 'dark');
    $logo = setting('logo_dark') && $theme !== 'light' ? setting('logo_dark') : setting('logo');
    $nav = app(\App\Support\SiteData::class);
@endphp
<!DOCTYPE html>
<html lang="en" class="{{ $theme === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    @if ($metaDescription)<meta name="description" content="{{ $metaDescription }}">@endif
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    @if ($noindex)<meta name="robots" content="noindex, follow">@endif
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title ?: $fullTitle }}">
    @if ($metaDescription)<meta property="og:description" content="{{ $metaDescription }}">@endif
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    @if ($ogImage)<meta property="og:image" content="{{ $ogImage }}"><meta name="twitter:card" content="summary_large_image">@endif
    @if (setting('google_site_verification'))<meta name="google-site-verification" content="{{ setting('google_site_verification') }}">@endif
    @if (setting('bing_site_verification'))<meta name="msvalidate.01" content="{{ setting('bing_site_verification') }}">@endif
    @if (setting('favicon'))<link rel="icon" href="{{ asset('storage/'.setting('favicon')) }}">@endif
    @if ($theme === 'auto')
        <script>if (matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.classList.add('dark');</script>
    @endif
    <style>:root, .dark { --c-brand: {{ setting('primary_color', '#22c55e') }}; }</style>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (setting('ads_enabled', true) && setting('adsense_publisher_id'))
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-{{ setting('adsense_publisher_id') }}" crossorigin="anonymous"></script>
    @endif
    @if (setting('google_analytics_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ setting('google_analytics_id') }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',@json(setting('google_analytics_id')));</script>
    @endif
    {!! setting('consent_cmp_code') !!}
    {!! setting('head_code') !!}
    @if (setting('custom_css'))<style>{!! strip_tags(setting('custom_css')) !!}</style>@endif
    {{ $head ?? '' }}
</head>
<body class="min-h-screen">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-brand focus:px-3 focus:py-2 focus:text-brand-ink">Skip to content</a>

<header class="border-b border-rule bg-surface">
    <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3">
        <button type="button" class="-ml-1 rounded p-1.5 text-muted hover:text-ink lg:hidden" data-toggle="mobile-nav" aria-controls="mobile-nav" aria-expanded="false" aria-label="Open menu">
            <x-ui-icon name="menu" class="size-6"/>
        </button>
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="{{ $siteName }} home">
            @if ($logo)
                <img src="{{ asset('storage/'.$logo) }}" alt="{{ $siteName }}" class="h-8 w-auto">
            @else
                <span class="grid size-8 place-items-center rounded-md bg-brand text-brand-ink"><x-ui-icon name="download" class="size-5"/></span>
                <span class="font-display text-xl font-bold">{{ $siteName }}</span>
            @endif
        </a>

        <form action="{{ route('search') }}" method="get" role="search" class="mx-auto hidden w-full max-w-xl md:block">
            <label for="q" class="sr-only">Search downloads</label>
            <div class="relative">
                <x-ui-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted"/>
                <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Search apps, games, templates…"
                       class="field h-11 pl-10 pr-24">
                <button class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded bg-brand px-3 py-1.5 text-sm font-semibold text-brand-ink">Search</button>
            </div>
        </form>

        <nav class="ml-auto hidden items-center gap-1 lg:flex" aria-label="Main">
            @foreach ($nav->menu('header') as $link)
                <a href="{{ $link->url }}" @if ($link->new_tab) target="_blank" rel="noopener" @endif
                   class="rounded px-3 py-2 text-sm font-medium text-muted hover:text-ink {{ url($link->url) === url()->current() ? 'text-ink' : '' }}">{{ $link->label }}</a>
            @endforeach
        </nav>
    </div>

    <form action="{{ route('search') }}" method="get" role="search" class="px-4 pb-3 md:hidden">
        <label for="q-mobile" class="sr-only">Search downloads</label>
        <input id="q-mobile" name="q" type="search" value="{{ request('q') }}" placeholder="Search downloads" class="field h-10">
    </form>

    <nav id="mobile-nav" class="border-t border-rule px-4 py-2 lg:hidden" aria-label="Main" hidden>
        @foreach ($nav->menu('header') as $link)
            <a href="{{ $link->url }}" class="block py-2 font-medium">{{ $link->label }}</a>
        @endforeach
    </nav>

    {{-- Category strip: the catalog's top level, always one tap away --}}
    <nav class="border-t border-rule" aria-label="Categories">
        <div class="scrollbar-none mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4">
            @foreach ($nav->categories() as $cat)
                <a href="{{ route('categories.show', $cat) }}"
                   class="flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2.5 text-sm font-medium {{ request()->route('category')?->id === $cat->id || request()->route('category')?->parent_id === $cat->id ? 'border-brand text-ink' : 'border-transparent text-muted hover:text-ink' }}">
                    <x-ui-icon :name="$cat->icon ?? 'folder'" class="size-4"/>{{ $cat->name }}
                </a>
            @endforeach
        </div>
    </nav>
</header>

<x-ad-slot zone="header_leaderboard" :category="$category" class="mx-auto hidden max-w-7xl px-4 pt-4 md:flex"/>
<x-ad-slot zone="header_mobile" :category="$category" class="px-4 pt-3 md:hidden"/>

<div class="mx-auto max-w-7xl px-4 py-6">
    @if ($sidebar)
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
            <main id="main" class="min-w-0">{{ $slot }}</main>
            <aside class="space-y-6" aria-label="Sidebar">
                <x-ad-slot zone="sidebar_top" :category="$category"/>
                {{ $aside ?? '' }}
                @include('partials.sidebar-popular')
                <x-ad-slot zone="sidebar_middle" :category="$category" class="lg:sticky lg:top-4"/>
                @include('partials.sidebar-categories')
                <x-ad-slot zone="sidebar_bottom" :category="$category"/>
            </aside>
        </div>
    @else
        <main id="main">{{ $slot }}</main>
    @endif
</div>

<x-ad-slot zone="footer_leaderboard" :category="$category" class="mx-auto max-w-7xl px-4 pb-6"/>

<footer class="border-t border-rule bg-surface">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="font-display text-lg font-bold">{{ $siteName }}</p>
            <p class="mt-2 text-sm text-muted">{{ setting('footer_text') ?: setting('site_tagline') }}</p>
            @if ($social = setting('social_links'))
                <p class="mt-3 flex flex-wrap gap-3 text-sm">
                    @foreach ($social as $network => $url)
                        <a href="{{ $url }}" rel="noopener me" target="_blank" class="text-muted hover:text-ink">{{ ucfirst($network) }}</a>
                    @endforeach
                </p>
            @endif
        </div>
        <div>
            <p class="font-semibold">Browse</p>
            <ul class="mt-2 space-y-1.5 text-sm">
                @foreach ($nav->categories()->take(6) as $cat)
                    <li><a href="{{ route('categories.show', $cat) }}" class="text-muted hover:text-ink">{{ $cat->name }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <p class="font-semibold">Lists</p>
            <ul class="mt-2 space-y-1.5 text-sm">
                <li><a href="{{ route('popular') }}" class="text-muted hover:text-ink">Most downloaded</a></li>
                <li><a href="{{ route('latest') }}" class="text-muted hover:text-ink">New and updated</a></li>
                <li><a href="{{ route('top-rated') }}" class="text-muted hover:text-ink">Top rated</a></li>
                <li><a href="{{ route('blog.index') }}" class="text-muted hover:text-ink">Blog</a></li>
            </ul>
        </div>
        <div>
            <p class="font-semibold">About</p>
            <ul class="mt-2 space-y-1.5 text-sm">
                @foreach ($nav->footerPages() as $page)
                    <li><a href="{{ route('pages.show', $page->slug) }}" class="text-muted hover:text-ink">{{ $page->title }}</a></li>
                @endforeach
                @foreach ($nav->menu('footer') as $link)
                    <li><a href="{{ $link->url }}" class="text-muted hover:text-ink">{{ $link->label }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="border-t border-rule py-4 text-center text-xs text-muted">
        © {{ date('Y') }} {{ $siteName }}. Downloads link to their publishers; trademarks belong to their owners.
    </div>
</footer>

<x-ad-slot zone="sticky_mobile_footer" class="fixed inset-x-0 bottom-0 z-40 bg-surface/95 py-1 md:hidden"/>

@if (setting('consent_banner_enabled', true) && ! setting('consent_cmp_code'))
    <div id="cookie-notice" class="fixed inset-x-3 bottom-3 z-50 mx-auto flex max-w-xl items-center gap-3 rounded-lg border border-rule bg-raised p-3 text-sm shadow-lg md:bottom-4" role="region" aria-label="Cookie notice" hidden>
        <p class="flex-1 text-muted">We use cookies for sign-in, analytics and ads.
            @if ($nav->footerPages()->firstWhere('slug', 'cookie-policy'))<a href="{{ route('pages.show', 'cookie-policy') }}" class="text-ink underline">Cookie policy</a>@endif
        </p>
        <button type="button" class="rounded bg-brand px-3 py-1.5 font-semibold text-brand-ink">OK</button>
    </div>
@endif

{!! setting('live_chat_code') !!}
{!! setting('body_end_code') !!}
</body>
</html>
