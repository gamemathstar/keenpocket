@props(['size' => 96, 'pose' => 'wave'])
{{-- "K" the Wise Contributor — the KeenPocket mascot (cropped from the MrK illustration).
     pose="cheer" uses the celebration cutout when present, else falls back to the wave. --}}
@php
    $cheer = $pose === 'cheer' && file_exists(public_path('ant-k/MrK-cheer-portrait.png'));
    $src = $cheer ? 'ant-k/MrK-cheer-portrait.png' : 'ant-k/MrK-portrait.png';
@endphp
<img src="{{ asset($src) }}" alt="K the Wise Contributor"
     width="{{ $size }}" {{ $attributes->merge(['class' => 'inline-block select-none rounded-xl']) }}>
