@props(['name'])

<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.7', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('brand')
        @case('crop')
            <path d="M12 21V10" />
            <path d="M12 14c-4.5 0-7-2.5-7-7 4.5 0 7 2.5 7 7Z" />
            <path d="M12 11c0-4.5 2.5-7 7-7 0 4.5-2.5 7-7 7Z" />
            @break
        @case('dashboard')
            <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" />
            <rect x="13.5" y="3.5" width="7" height="4" rx="1.5" />
            <rect x="13.5" y="10.5" width="7" height="10" rx="1.5" />
            <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
            @break
        @case('land')
            <path d="m3 9 9-5 9 5v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z" />
            <path d="M3 14c3 0 3-2 6-2s3 2 6 2 3-2 6-2M9 20v-3m6 3v-3" />
            @break
        @case('activity')
            <path d="M3 12h4l3-8 4 16 3-8h4" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 10h18" />
            <path d="M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01" />
            @break
        @case('harvest')
            <path d="M12 21V4m0 6c-3 0-5-2-5-5 3 0 5 2 5 5Zm0 4c3 0 5-2 5-5-3 0-5 2-5 5Zm-5 7h10" />
            @break
        @case('sales')
            <path d="M4 19V5m0 14h16" />
            <path d="m7 15 4-4 3 2 5-6" />
            <path d="M16 7h3v3" />
            @break
        @case('report')
            <path d="M4 19V5m0 14h16" />
            <path d="M7 15v-3m5 3V7m5 8v-5" />
            @break
        @case('expense')
            <path d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
            <path d="M14 3v5h5M8 12h8m-8 4h8" />
            @break
        @case('product')
            <path d="m12 3 9 5-9 5-9-5 9-5Z" />
            <path d="m3 8 9 5 9-5m-18 0v9l9 5 9-5V8m-9 5v9" />
            @break
        @case('note')
            <path d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
            <path d="M14 3v5h5M8 12h8m-8 4h6" />
            @break
        @case('profile')
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
            @break
        @case('logout')
            <path d="M10 17l5-5-5-5m5 5H3" />
            <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" />
            @break
    @endswitch
</svg>
