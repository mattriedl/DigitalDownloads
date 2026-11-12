<nav class="mb-5 flex gap-1 border-b border-rule text-sm" aria-label="Account">
    @foreach (['account' => 'My downloads', 'tickets.index' => 'Support', 'account.settings' => 'Settings'] as $route => $label)
        <a href="{{ route($route) }}" @if (request()->routeIs($route === 'tickets.index' ? 'tickets.*' : $route)) aria-current="page" @endif
           class="-mb-px border-b-2 px-3 py-2 font-medium {{ request()->routeIs($route === 'tickets.index' ? 'tickets.*' : $route) ? 'border-brand text-ink' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
    @endforeach
</nav>
