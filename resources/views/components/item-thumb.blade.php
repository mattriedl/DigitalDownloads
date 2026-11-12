@props(['item', 'class' => 'size-14'])
@if ($item->thumbnail)
    <img src="{{ asset('storage/'.$item->thumbnail) }}" alt="" loading="lazy" class="{{ $class }} shrink-0 rounded-md bg-raised object-cover">
@else
    {{-- No image yet: a lettered tile in a color derived from the title, so lists stay scannable --}}
    @php
        $hue = crc32($item->title) % 360;
        $initials = collect(preg_split('/\s+/', trim($item->title)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    @endphp
    <span class="{{ $class }} grid shrink-0 place-items-center rounded-md font-display font-bold text-white"
          style="background: hsl({{ $hue }} 45% 38%)" aria-hidden="true">{{ $initials }}</span>
@endif
