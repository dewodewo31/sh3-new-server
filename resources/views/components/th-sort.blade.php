@props(['column' => null])

<th {{ $attributes->merge(['class' => '']) }}>
    @if ($column)
        <a href="{{ \App\Support\Sort::url($column) }}"
           class="th-sort {{ \App\Support\Sort::active($column) ? 'active' : '' }} {{ \App\Support\Sort::isAsc($column) ? 'asc' : 'desc' }}"
           title="Urutkan kolom"
           aria-label="Urutkan kolom ini">
            <span>{{ $slot }}</span>
            <svg class="th-sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M8 7l4-4 4 4"/>
                <path d="M16 17l-4 4-4-4"/>
            </svg>
        </a>
    @else
        <span>{{ $slot }}</span>
    @endif
</th>