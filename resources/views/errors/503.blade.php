@include('errors._standalone', [
    'heading' => 'Back in a few minutes',
    'text' => isset($exception) && $exception->getMessage() && ! str_contains($exception->getMessage(), 'Service Unavailable')
        ? $exception->getMessage()
        : 'The site is being updated. Refresh the page in a minute or two.',
])
