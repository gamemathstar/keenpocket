@php
    // Brand palette — Duolingo-style, recolored blue.
    $brand = ['DEFAULT' => '#1cb0f6', 'dark' => '#1899d6', 'light' => '#ddf4ff'];

    // Use the compiled Vite/Tailwind build when present; otherwise the CDN.
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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">

@if ($cssHref)
    <link rel="stylesheet" href="{{ $cssHref }}">
@else
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { brand: @json($brand) },
            fontFamily: { sans: ['Nunito', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
        } } };
    </script>
@endif

{{-- Duolingo-flavoured base layer (applies in both CDN and built modes) --}}
<style>
    :root { --brand: {{ $brand['DEFAULT'] }}; --brand-dark: {{ $brand['dark'] }}; }
    body { font-family: 'Nunito', ui-sans-serif, system-ui, sans-serif; color: #3c3c3c; font-weight: 600; }
    h1, h2, h3, h4 { font-weight: 800; }

    /* Friendlier, rounder corners everywhere */
    .rounded-lg { border-radius: .8rem !important; }
    .rounded-xl { border-radius: 1.1rem !important; }
    .rounded-2xl { border-radius: 1.4rem !important; }

    /* Signature chunky 3D primary buttons (any <button>/<a> with the brand bg) */
    button.bg-brand, a.bg-brand {
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .02em;
        border: none;
        box-shadow: 0 4px 0 0 var(--brand-dark);
        transition: transform .05s ease, box-shadow .05s ease, filter .15s ease;
    }
    button.bg-brand:hover, a.bg-brand:hover { filter: brightness(1.03); }
    button.bg-brand:active, a.bg-brand:active { transform: translateY(3px); box-shadow: 0 1px 0 0 var(--brand-dark); }

    /* Duolingo-style raised cards: thicker 2px border + 4px bottom edge */
    .bg-white.border { border-width: 2px; border-bottom-width: 4px; border-color: #e5e7eb; }
</style>
