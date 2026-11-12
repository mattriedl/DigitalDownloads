<x-layout
    :title="$category->seo_title ?: $category->name.' downloads'"
    :description="$category->seo_description ?: $category->description"
    :canonical="route('categories.show', $category).($items->currentPage() > 1 ? '?page='.$items->currentPage() : '')"
    :category="$category->id">
    @include('partials.breadcrumbs', ['crumbs' => ['Categories' => route('categories.index')]
        + ($category->parent ? [$category->parent->name => route('categories.show', $category->parent)] : [])
        + [$category->name => null]])

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="flex items-center gap-2 font-display text-3xl font-bold">
                <x-ui-icon :name="$category->icon ?? $category->parent?->icon ?? 'folder'" class="size-7 text-brand"/>{{ $category->name }}
            </h1>
            @if ($category->description)
                <p class="mt-1 max-w-[70ch] text-muted">{{ $category->description }}</p>
            @endif
        </div>
        <form method="get" class="flex items-center gap-2 text-sm">
            <label for="sort" class="text-muted">Sort by</label>
            <select id="sort" name="sort" class="field w-auto py-1.5" onchange="this.form.submit()">
                @foreach (\App\Http\Controllers\CategoryController::SORTS as $key => $label)
                    <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <noscript><button class="btn-quiet">Apply</button></noscript>
        </form>
    </div>

    @if ($category->children->isNotEmpty())
        <ul class="mt-4 flex flex-wrap gap-2">
            @foreach ($category->children as $child)
                <li><a href="{{ route('categories.show', $child) }}" class="btn-quiet py-1">{{ $child->name }} <span class="text-muted">{{ $child->items_count }}</span></a></li>
            @endforeach
        </ul>
    @endif

    <x-ad-slot zone="category_top" :category="$category->id" class="mt-5"/>

    <div class="panel mt-5 overflow-hidden">
        @forelse ($items as $item)
            <x-item-row :item="$item"/>
            @if ($loop->iteration === 8)
                <x-ad-slot zone="home_between_sections" :category="$category->id" class="border-b border-rule py-4"/>
            @endif
        @empty
            <p class="p-6 text-muted">Nothing in this category yet. Check back soon or <a href="{{ route('latest') }}" class="text-brand underline">see what's new</a>.</p>
        @endforelse
    </div>

    {{ $items->links('partials.pagination') }}
</x-layout>
