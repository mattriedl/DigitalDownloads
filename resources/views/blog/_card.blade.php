<article class="group relative overflow-hidden rounded-lg border border-rule bg-surface hover:border-brand">
    @if ($post->image)
        <img src="{{ \App\Support\Images::url($post->image, 800) }}" alt="" loading="lazy" decoding="async" width="800" height="450" class="aspect-[16/9] w-full object-cover">
    @endif
    <div class="p-4">
        <h3 class="font-display text-lg font-semibold leading-snug">
            <a href="{{ route('blog.show', $post) }}" class="after:absolute after:inset-0 group-hover:text-brand">{{ $post->title }}</a>
        </h3>
        @if ($post->excerpt)<p class="mt-1 line-clamp-3 text-sm text-muted">{{ $post->excerpt }}</p>@endif
        <p class="mt-2 text-xs text-muted"><time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('M j, Y') }}</time></p>
    </div>
</article>
