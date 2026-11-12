<x-layout>
    <h1 class="sr-only">{{ setting('site_name') }}: {{ setting('site_tagline') }}</h1>

    @if ($featured->isNotEmpty())
        <section aria-labelledby="featured">
            <div class="mb-3 flex items-baseline justify-between">
                <h2 id="featured" class="section-title text-2xl">Featured</h2>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($featured as $item)
                    <x-item-tile :item="$item"/>
                @endforeach
            </div>
        </section>
    @endif

    <x-ad-slot zone="home_between_sections" class="my-6"/>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="panel overflow-hidden" aria-labelledby="latest">
            <div class="flex items-baseline justify-between border-b border-rule px-4 py-3">
                <h2 id="latest" class="section-title">New and updated</h2>
                <a href="{{ route('latest') }}" class="text-sm text-brand hover:underline">See all</a>
            </div>
            @foreach ($latest as $item)
                <x-item-row :item="$item" compact/>
            @endforeach
        </section>

        <section class="panel overflow-hidden" aria-labelledby="popular">
            <div class="flex items-baseline justify-between border-b border-rule px-4 py-3">
                <h2 id="popular" class="section-title">Trending this week</h2>
                <a href="{{ route('popular') }}" class="text-sm text-brand hover:underline">See all</a>
            </div>
            @foreach ($trending as $item)
                <x-item-row :item="$item" :rank="$loop->iteration" compact/>
            @endforeach
        </section>
    </div>

    @foreach ($sections as $section)
        <section class="panel mt-6 overflow-hidden" aria-labelledby="cat-{{ $section['category']->id }}">
            <div class="flex items-center justify-between border-b border-rule px-4 py-3">
                <h2 id="cat-{{ $section['category']->id }}" class="section-title flex items-center gap-2">
                    <x-ui-icon :name="$section['category']->icon ?? 'folder'" class="size-5 text-brand"/>
                    {{ $section['category']->name }}
                </h2>
                <a href="{{ route('categories.show', $section['category']) }}" class="text-sm text-brand hover:underline">All {{ $section['category']->name }}</a>
            </div>
            <div class="grid md:grid-cols-2 md:[&>article:nth-last-child(-n+2)]:border-b-0">
                @foreach ($section['items'] as $item)
                    <x-item-row :item="$item" compact/>
                @endforeach
            </div>
        </section>
        @if ($loop->iteration % 2 === 0 && ! $loop->last)
            <x-ad-slot zone="home_between_sections" class="mt-6"/>
        @endif
    @endforeach

    @if ($posts->isNotEmpty())
        <section class="mt-8" aria-labelledby="from-blog">
            <div class="mb-3 flex items-baseline justify-between">
                <h2 id="from-blog" class="section-title text-2xl">From the blog</h2>
                <a href="{{ route('blog.index') }}" class="text-sm text-brand hover:underline">All articles</a>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($posts as $post)
                    @include('blog._card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif
</x-layout>
