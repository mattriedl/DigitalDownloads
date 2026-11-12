<x-layout :title="$page->seo_title ?: $page->title" :description="$page->seo_description">
    @include('partials.breadcrumbs', ['crumbs' => [$page->title => null]])
    <article>
        <h1 class="font-display text-4xl font-bold">{{ $page->title }}</h1>
        <p class="mt-1 text-sm text-muted">Last updated {{ $page->updated_at->format('F j, Y') }}</p>
        <div class="prose-site mt-6">{!! safe_html($page->body) !!}</div>
    </article>
</x-layout>
