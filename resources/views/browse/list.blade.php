<x-layout :title="$title" :description="$intro">
    @include('partials.breadcrumbs', ['crumbs' => [$title => null]])
    <h1 class="font-display text-3xl font-bold">{{ $title }}</h1>
    @if ($intro)<p class="mt-1 text-muted">{{ $intro }}</p>@endif

    <x-ad-slot zone="category_top" class="mt-5"/>

    <div class="panel mt-5 overflow-hidden">
        @forelse ($items as $item)
            <x-item-row :item="$item" :rank="$ranked ? $items->firstItem() + $loop->index : null"/>
        @empty
            <p class="p-6 text-muted">No downloads here yet.</p>
        @endforelse
    </div>

    {{ $items->links('partials.pagination') }}
</x-layout>
