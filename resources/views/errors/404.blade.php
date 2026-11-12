<x-layout title="Page not found" :noindex="true">
    <div class="panel p-8">
        <h1 class="font-display text-4xl font-bold">We couldn't find that page</h1>
        <p class="mt-2 max-w-[60ch] text-muted">The download may have been removed or its address changed. Search for it, or start from the categories.</p>
        <form action="{{ route('search') }}" method="get" role="search" class="mt-5 flex max-w-lg gap-2">
            <label for="nf-q" class="sr-only">Search downloads</label>
            <input id="nf-q" name="q" type="search" class="field" placeholder="Search downloads">
            <button class="btn-download py-2 text-base">Search</button>
        </form>
        <p class="mt-4"><a href="{{ route('categories.index') }}" class="text-brand underline">Browse all categories</a></p>
    </div>
</x-layout>
