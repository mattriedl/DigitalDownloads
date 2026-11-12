@php $cats = app(\App\Support\SiteData::class)->categories(); @endphp
@if ($cats->isNotEmpty())
    <section class="panel p-4" aria-labelledby="sidebar-categories">
        <h2 id="sidebar-categories" class="section-title text-lg">Categories</h2>
        <ul class="mt-3 space-y-0.5 text-sm">
            @foreach ($cats as $cat)
                <li>
                    <a href="{{ route('categories.show', $cat) }}" class="flex items-center justify-between rounded px-2 py-1.5 hover:bg-raised">
                        <span class="flex items-center gap-2"><x-ui-icon :name="$cat->icon ?? 'folder'" class="size-4 text-muted"/>{{ $cat->name }}</span>
                        <span class="text-xs text-muted">{{ number_format($cat->items_count + $cat->children->sum('items_count')) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
