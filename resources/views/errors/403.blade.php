@include('errors._message', [
    'heading' => 'You can’t open this page',
    'text' => $exception->getMessage() ?: 'Your account doesn’t have access to it. If you think it should, contact us.',
])
