<x-layout title="All categories" description="Browse every download category on {{ setting('site_name') }}.">
    @include('partials.breadcrumbs', ['crumbs' => ['Categories' => null]])
    <h1 class="font-display text-3xl font-bold">All categories</h1>

    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        @forelse ($categories as $cat)
            <section class="panel p-4">
                <h2 class="flex items-center justify-between">
                    <a href="{{ route('categories.show', $cat) }}" class="flex items-center gap-2 font-display text-xl font-semibold hover:text-brand">
                        <x-ui-icon :name="$cat->icon ?? 'folder'" class="size-5 text-brand"/>{{ $cat->name }}
                    </a>
                    <span class="text-sm text-muted">{{ number_format($cat->items_count + $cat->children->sum('items_count')) }}</span>
                </h2>
                @if ($cat->children->isNotEmpty())
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($cat->children as $child)
                            <li><a href="{{ route('categories.show', $child) }}" class="btn-quiet py-1">{{ $child->name }} <span class="text-muted">{{ $child->items_count }}</span></a></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @empty
            <p class="text-muted">No categories yet. Add them in the admin under Catalog → Categories.</p>
        @endforelse
    </div>
</x-layout>
