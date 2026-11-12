<x-layout :title="$title" :description="$description ?? null">
    @include('partials.breadcrumbs', ['crumbs' => $heading === 'Blog' ? ['Blog' => null] : ['Blog' => route('blog.index'), $heading => null]])
    <h1 class="font-display text-3xl font-bold">{{ $heading }}</h1>

    @if ($categories->isNotEmpty())
        <ul class="mt-4 flex flex-wrap gap-2 text-sm">
            <li><a href="{{ route('blog.index') }}" class="btn-quiet py-1 {{ $heading === 'Blog' ? 'border-brand' : '' }}">All</a></li>
            @foreach ($categories as $cat)
                <li><a href="{{ route('blog.category', $cat) }}" class="btn-quiet py-1 {{ $heading === $cat->name ? 'border-brand' : '' }}">{{ $cat->name }}</a></li>
            @endforeach
        </ul>
    @endif

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($posts as $post)
            @include('blog._card', ['post' => $post])
        @empty
            <p class="text-muted">No articles yet.</p>
        @endforelse
    </div>

    {{ $posts->links('partials.pagination') }}
</x-layout>
