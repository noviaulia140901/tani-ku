@props(['label', 'value', 'detail', 'tone' => 'green', 'icon' => 'dashboard', 'compact' => false])

@php
    $tones = [
        'green' => 'bg-[#E7F1E2] text-[#2F6B4F]',
        'red' => 'bg-rose-100 text-rose-800',
        'amber' => 'bg-[#FBF0D9] text-[#855D16]',
        'blue' => 'bg-[#DCEAF0] text-[#315B6B]',
    ];
@endphp

<article class="rounded-2xl border border-[#E9E7DE] bg-white p-5 shadow-[0_5px_22px_rgba(44,61,45,0.04)] sm:p-6">
    <div class="flex items-start justify-between gap-3">
        <p @class([
            'font-semibold text-gray-600',
            'text-[13px] sm:text-sm' => $compact,
            'text-sm' => ! $compact,
        ])>{{ $label }}</p>
        <span aria-hidden="true" class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['green'] }}">
            <x-navigation-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
    <p @class([
        'mt-5 break-words font-bold tabular-nums tracking-tight text-gray-950',
        'text-xl sm:text-2xl' => $compact,
        'text-2xl' => ! $compact,
    ])>{{ $value }}</p>
    <p @class([
        'mt-1 leading-5 text-gray-500',
        'text-xs sm:text-sm' => $compact,
        'text-sm' => ! $compact,
    ])>{{ $detail }}</p>
</article>
