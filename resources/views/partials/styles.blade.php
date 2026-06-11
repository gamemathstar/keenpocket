@php
    // Use the compiled Vite/Tailwind build when present; otherwise fall back to
    // the Tailwind Play CDN (works on any Laravel version — no @vite directive).
    $cssHref = null;
    $manifestPath = public_path('build/manifest.json');
    if (is_file($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $entry = $manifest['resources/css/app.css'] ?? null;
        if (!empty($entry['file'])) {
            $cssHref = asset('build/'.$entry['file']);
        }
    }
@endphp
@if ($cssHref)
    <link rel="stylesheet" href="{{ $cssHref }}">
@else
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#059669', dark: '#047857', light: '#d1fae5' } } } } };</script>
@endif
