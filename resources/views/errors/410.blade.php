@include('errors._message', [
    'heading' => 'This page has been removed',
    'text' => 'It was taken down on purpose and won’t be coming back. Search for something similar instead.',
    'action' => '<a href="'.e(route('search')).'" class="btn-quiet">Search downloads</a>',
])
