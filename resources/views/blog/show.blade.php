@php
    $faq = collect($post->faq ?? [])->filter(fn ($q) => filled($q['question'] ?? null) && filled($q['answer'] ?? null));
    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post->title,
        'description' => $post->excerpt,
        'image' => $post->image ? asset('storage/'.$post->image) : null,
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : null,
    ]);
    $faqSchema = $faq->isEmpty() ? null : [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faq->map(fn ($q) => [
            '@type' => 'Question',
            'name' => $q['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q['answer']],
        ])->values()->all(),
    ];
@endphp
<x-layout :title="$post->seo_title ?: $post->title" :description="$post->seo_description ?: $post->excerpt"
          :image="$post->image ? asset('storage/'.$post->image) : null">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        @if ($faqSchema)<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>@endif
    </x-slot:head>

    @include('partials.breadcrumbs', ['crumbs' => ['Blog' => route('blog.index')]
        + ($post->category ? [$post->category->name => route('blog.category', $post->category)] : [])
        + [$post->title => null]])

    <article>
        <h1 class="max-w-[24ch] font-display text-4xl font-bold leading-tight">{{ $post->title }}</h1>
        <p class="mt-2 text-sm text-muted">
            @if ($post->author){{ $post->author->name }}, @endif
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('F j, Y') }}</time>
        </p>

        @if ($post->image)
            <figure class="mt-5">
                <img src="{{ asset('storage/'.$post->image) }}" alt="" class="w-full rounded-lg border border-rule">
                @if ($post->image_credit)<figcaption class="mt-1 text-xs text-muted">{{ $post->image_credit }}</figcaption>@endif
            </figure>
        @endif

        <div class="prose-site mt-6">{!! safe_html($post->body) !!}</div>

        <x-ad-slot zone="blog_in_article" class="my-8"/>

        @if ($faq->isNotEmpty())
            <section class="mt-8 max-w-[72ch]" aria-labelledby="faq">
                <h2 id="faq" class="section-title text-2xl">Frequently asked questions</h2>
                <div class="mt-3 divide-y divide-rule rounded-lg border border-rule bg-surface">
                    @foreach ($faq as $q)
                        <details class="group px-4 py-3">
                            <summary class="cursor-pointer list-none font-semibold">{{ $q['question'] }}</summary>
                            <p class="mt-2 text-muted">{{ $q['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </article>

    @if ($more->isNotEmpty())
        <section class="mt-10" aria-labelledby="more-posts">
            <h2 id="more-posts" class="section-title mb-3 text-2xl">Keep reading</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($more as $other)
                    @include('blog._card', ['post' => $other])
                @endforeach
            </div>
        </section>
    @endif
</x-layout>
