@props(['size' => 96])
{{-- Keeny — the KeenPocket savings mascot (a friendly blue coin) --}}
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 120 120" fill="none" {{ $attributes }}>
    <ellipse cx="60" cy="112" rx="34" ry="6" fill="#000" opacity="0.06"/>
    <circle cx="60" cy="58" r="46" fill="#1cb0f6"/>
    <circle cx="60" cy="58" r="46" fill="none" stroke="#1899d6" stroke-width="6"/>
    <circle cx="60" cy="58" r="33" fill="#ddf4ff"/>
    <text x="60" y="70" text-anchor="middle" font-size="34" font-weight="900" fill="#1899d6" font-family="Nunito, sans-serif">₦</text>
    {{-- eyes --}}
    <circle cx="46" cy="40" r="8" fill="#fff"/>
    <circle cx="74" cy="40" r="8" fill="#fff"/>
    <circle cx="47" cy="41" r="3.5" fill="#3c3c3c"/>
    <circle cx="73" cy="41" r="3.5" fill="#3c3c3c"/>
    {{-- smile --}}
    <path d="M50 50 q10 9 20 0" stroke="#3c3c3c" stroke-width="3" stroke-linecap="round" fill="none"/>
</svg>
