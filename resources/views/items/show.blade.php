@php
    $parent = $item->category?->parent;
    $crumbs = ($parent ? [$parent->name => route('categories.show', $parent)] : [])
        + ($item->category ? [$item->category->name => route('categories.show', $item->category)] : [])
        + [$item->title => null];
    $details = array_filter([
        'Version' => $item->version,
        'File size' => $item->file_size,
        'File type' => $item->file_type,
        'License' => $item->license,
        'Works on' => $item->platforms,
        'Requirements' => $item->requirements,
        'Publisher' => $item->publisher,
        'Updated' => $item->updated_at?->format('M j, Y'),
        'Downloads' => number_format($item->download_count),
    ]);
    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => $item->title,
        'description' => $item->short_description,
        'softwareVersion' => $item->version,
        'operatingSystem' => $item->platforms,
        'applicationCategory' => $item->category?->name,
        'fileSize' => $item->file_size,
        'image' => $item->thumbnail ? asset('storage/'.$item->thumbnail) : null,
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        'aggregateRating' => $item->rating_count ? [
            '@type' => 'AggregateRating', 'ratingValue' => $item->rating_avg, 'ratingCount' => $item->rating_count,
        ] : null,
    ]);
@endphp
<x-layout :title="$item->metaTitle()" :description="$item->metaDescription()"
          :image="$item->thumbnail ? asset('storage/'.$item->thumbnail) : null"
          :noindex="$item->noindex || ! $item->isPublished()" :category="$item->category_id">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    @include('partials.breadcrumbs', ['crumbs' => $crumbs])

    @if (session('status'))
        <p class="mb-4 rounded-md border border-brand/40 bg-brand/10 px-4 py-3 text-sm" role="status">{{ session('status') }}</p>
    @endif
    @unless ($item->isPublished())
        <p class="mb-4 rounded-md border border-star/40 bg-star/10 px-4 py-3 text-sm">Preview: this item is not published, so only staff can see it.</p>
    @endunless

    <article>
        <header class="panel p-5">
            <div class="flex gap-4">
                <x-item-thumb :item="$item" class="size-20 text-2xl sm:size-24"/>
                <div class="min-w-0 flex-1">
                    <h1 class="font-display text-3xl font-bold leading-tight sm:text-4xl">{{ $item->title }}
                        @if ($item->version)<span class="ml-1 align-middle font-sans text-base font-medium text-muted">{{ $item->version }}</span>@endif
                    </h1>
                    @if ($item->publisher)
                        <p class="mt-1 text-sm text-muted">by
                            @if ($item->publisher_url)
                                <a href="{{ $item->publisher_url }}" rel="nofollow noopener" target="_blank" class="text-ink underline decoration-rule underline-offset-2 hover:decoration-brand">{{ $item->publisher }}</a>
                            @else
                                <span class="text-ink">{{ $item->publisher }}</span>
                            @endif
                        </p>
                    @endif
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted">
                        @if ($item->rating_count)
                            <a href="#reviews" class="hover:text-ink"><x-stars :rating="$item->rating_avg" :count="$item->rating_count"/></a>
                        @endif
                        <span>{{ number_format($item->download_count) }} downloads</span>
                        @if ($item->license)<span>{{ $item->license }}</span>@endif
                    </div>
                </div>
            </div>

            @if ($item->short_description)
                <p class="mt-4 max-w-[70ch] text-[15.5px]">{{ $item->short_description }}</p>
            @endif

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                @if ($primary = $item->activeLinks->first())
                    <a href="{{ route('items.get', [$item, $primary]) }}" class="btn-download sm:min-w-64" rel="nofollow">
                        <x-ui-icon name="download" class="size-6"/> Download {{ $item->file_size ? '('.$item->file_size.')' : '' }}
                    </a>
                @else
                    <p class="rounded-md border border-rule px-4 py-3 text-muted">No download available right now.</p>
                @endif
                @if ($item->activeLinks->count() > 1)
                    <details class="relative">
                        <summary class="btn-quiet cursor-pointer list-none">Other download links ({{ $item->activeLinks->count() - 1 }})</summary>
                        <ul class="absolute z-10 mt-2 w-72 rounded-md border border-rule bg-raised p-1 shadow-lg">
                            @foreach ($item->activeLinks->skip(1) as $link)
                                <li><a href="{{ route('items.get', [$item, $link]) }}" rel="nofollow" class="block rounded px-3 py-2 text-sm hover:bg-surface">{{ $link->label }} @if ($link->file_size)<span class="text-muted">{{ $link->file_size }}</span>@endif</a></li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @auth
                    <form method="post" action="{{ route('items.favorite', $item) }}">
                        @csrf
                        <button class="btn-quiet" aria-pressed="{{ $isSaved ? 'true' : 'false' }}">
                            <x-ui-icon name="star" class="size-4 {{ $isSaved ? 'fill-current text-star' : '' }}"/> {{ $isSaved ? 'Saved' : 'Save' }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-quiet"><x-ui-icon name="star" class="size-4"/> Save</a>
                @endauth
                <span class="flex items-center gap-1.5 text-xs text-muted sm:ml-auto"><x-ui-icon name="shield" class="size-4"/>Links checked daily</span>
            </div>
        </header>

        <x-ad-slot zone="item_below_download" :category="$item->category_id" class="my-5"/>

        @if ($item->screenshots->isNotEmpty())
            <section class="mt-6" aria-labelledby="screens">
                <h2 id="screens" class="section-title mb-3">Screenshots</h2>
                <div class="scrollbar-none -mx-4 flex snap-x gap-3 overflow-x-auto px-4">
                    @foreach ($item->screenshots as $shot)
                        <figure class="shrink-0 snap-start">
                            <a href="{{ asset('storage/'.$shot->path) }}" target="_blank">
                                <img src="{{ asset('storage/'.$shot->path) }}" alt="{{ $shot->caption ?: $item->title.' screenshot '.$loop->iteration }}" loading="lazy" class="h-48 w-auto rounded-md border border-rule sm:h-60">
                            </a>
                            @if ($shot->caption)<figcaption class="mt-1 text-xs text-muted">{{ $shot->caption }}</figcaption>@endif
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($item->video_url && preg_match('~(?:youtu\.be/|v=|embed/)([\w-]{11})~', $item->video_url, $yt))
            <div class="mt-6 aspect-video overflow-hidden rounded-lg border border-rule">
                <iframe src="https://www.youtube-nocookie.com/embed/{{ $yt[1] }}" title="{{ $item->title }} video" loading="lazy" class="size-full" allowfullscreen></iframe>
            </div>
        @endif

        <x-ad-slot zone="item_above_details" :category="$item->category_id" class="my-6"/>

        <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_240px]">
            <section aria-labelledby="about">
                <h2 id="about" class="section-title mb-2">About {{ $item->title }}</h2>
                <div class="prose-site">
                    {!! $item->description ?: '<p>'.e($item->short_description).'</p>' !!}
                </div>
                <x-ad-slot zone="item_in_description" :category="$item->category_id" class="my-6"/>
            </section>

            <aside aria-labelledby="details">
                <h2 id="details" class="section-title mb-2">Details</h2>
                <dl class="panel divide-y divide-rule text-sm">
                    @foreach ($details as $label => $value)
                        <div class="flex justify-between gap-3 px-3 py-2">
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd class="text-right font-medium">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($item->tags->isNotEmpty())
                    <ul class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($item->tags as $tag)
                            <li><a href="{{ route('tags.show', $tag) }}" class="rounded-full border border-rule px-2.5 py-0.5 text-xs text-muted hover:border-brand hover:text-ink">{{ $tag->name }}</a></li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" data-open-dialog="report" class="mt-4 flex items-center gap-1.5 text-sm text-muted hover:text-ink" id="report">
                    <x-ui-icon name="flag" class="size-4"/> Report a problem
                </button>
            </aside>
        </div>

        @if ($item->changelog || $item->versions->isNotEmpty())
            <section class="mt-8" aria-labelledby="changes">
                <h2 id="changes" class="section-title mb-3">What's new</h2>
                @if ($item->changelog)
                    <div class="prose-site whitespace-pre-line">{{ $item->changelog }}</div>
                @endif
                @if ($item->versions->isNotEmpty())
                    <ol class="panel mt-3 divide-y divide-rule">
                        @foreach ($item->versions as $version)
                            <li class="px-4 py-3">
                                <p class="font-semibold">{{ $version->version }}
                                    @if ($version->released_at)<span class="ml-2 text-sm font-normal text-muted">{{ $version->released_at->format('M j, Y') }}</span>@endif
                                </p>
                                @if ($version->notes)<p class="mt-1 whitespace-pre-line text-sm text-muted">{{ $version->notes }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @endif

        <section class="mt-8" id="reviews" aria-labelledby="reviews-title">
            <h2 id="reviews-title" class="section-title mb-3">Reviews</h2>
            @if ($item->rating_count)
                <p class="mb-3 flex items-center gap-3">
                    <span class="font-display text-4xl font-bold">{{ number_format($item->rating_avg, 1) }}</span>
                    <x-stars :rating="$item->rating_avg" :count="$item->rating_count" size="size-5"/>
                </p>
            @endif
            @forelse ($item->reviews as $review)
                <article class="border-t border-rule py-4">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <x-stars :rating="$review->rating"/>
                        @if ($review->title)<h3 class="font-semibold">{{ $review->title }}</h3>@endif
                    </div>
                    @if ($review->body)<p class="mt-1 text-sm">{{ $review->body }}</p>@endif
                    <p class="mt-1 text-xs text-muted">{{ $review->user?->name ?? 'Former member' }}, {{ $review->created_at->format('M j, Y') }}</p>
                </article>
            @empty
                <p class="text-sm text-muted">No reviews yet. Be the first to say how it worked for you.</p>
            @endforelse

            @if (setting('reviews_enabled', true))
                @include('items._review-form')
            @endif
        </section>

        @if ($item->allow_comments && setting('comments_enabled', true))
            <section class="mt-8" id="comments" aria-labelledby="comments-title">
                <h2 id="comments-title" class="section-title mb-3">Comments</h2>
                @include('items._comment-form')
                @if ($item->comments->isEmpty())
                    <p class="mt-3 text-sm text-muted">No comments yet.</p>
                @endif
                @foreach ($item->comments as $comment)
                    <article class="border-t border-rule py-4">
                        <p class="text-sm font-semibold">{{ $comment->user?->name ?? 'Former member' }} <span class="font-normal text-muted">{{ $comment->created_at->diffForHumans() }}</span></p>
                        <p class="mt-1 text-sm">{{ $comment->body }}</p>
                        @foreach ($comment->replies as $reply)
                            <div class="ml-5 mt-3 border-l-2 border-rule pl-3">
                                <p class="text-sm font-semibold">{{ $reply->user?->name ?? 'Former member' }} <span class="font-normal text-muted">{{ $reply->created_at->diffForHumans() }}</span></p>
                                <p class="mt-1 text-sm">{{ $reply->body }}</p>
                            </div>
                        @endforeach
                    </article>
                @endforeach
            </section>
        @endif
    </article>

    @if ($related->isNotEmpty())
        <section class="panel mt-8 overflow-hidden" aria-labelledby="related">
            <h2 id="related" class="section-title border-b border-rule px-4 py-3">More in {{ $item->category?->name }}</h2>
            <div class="grid md:grid-cols-2">
                @foreach ($related as $other)
                    <x-item-row :item="$other" compact/>
                @endforeach
            </div>
        </section>
    @endif

    <dialog id="report" class="m-auto w-[min(32rem,calc(100%-2rem))] rounded-lg border border-rule bg-surface p-0 text-ink backdrop:bg-black/60">
        <form method="post" action="{{ route('items.report', $item) }}" class="space-y-4 p-5">
            @csrf
            <div class="flex items-start justify-between">
                <h2 class="font-display text-xl font-semibold">Report a problem with {{ $item->title }}</h2>
                <button type="button" data-close-dialog class="text-muted hover:text-ink" aria-label="Close"><x-ui-icon name="x"/></button>
            </div>
            <fieldset class="space-y-2">
                <legend class="mb-1 text-sm font-medium">What's wrong?</legend>
                @foreach ($reportTypes as $value => $label)
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="type" value="{{ $value }}" @checked(old('type', 'broken_link') === $value) class="accent-[var(--c-brand)]"> {{ $label }}</label>
                @endforeach
            </fieldset>
            <div>
                <label for="report-message" class="mb-1 block text-sm font-medium">Details</label>
                <textarea id="report-message" name="message" rows="4" class="field" maxlength="3000">{{ old('message') }}</textarea>
                <p class="mt-1 text-xs text-muted">For copyright complaints, name the work and show where it's yours.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="report-name" class="mb-1 block text-sm font-medium">Your name</label>
                    <input id="report-name" name="name" value="{{ old('name') }}" class="field" maxlength="120" autocomplete="name">
                </div>
                <div>
                    <label for="report-email" class="mb-1 block text-sm font-medium">Email</label>
                    <input id="report-email" type="email" name="email" value="{{ old('email') }}" class="field" maxlength="190" autocomplete="email">
                </div>
            </div>
            <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
            @if ($errors->any())
                <ul class="rounded-md border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-300">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            @endif
            <div class="flex justify-end gap-2">
                <button type="button" data-close-dialog class="btn-quiet">Cancel</button>
                <button class="rounded-md bg-brand px-4 py-2 font-semibold text-brand-ink">Send report</button>
            </div>
        </form>
    </dialog>
    @if ($errors->any())
        <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('report')?.showModal());</script>
    @endif
</x-layout>
