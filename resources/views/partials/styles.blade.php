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

    /* Distinctive KeenPocket cards: 3D edge + subtle sheen + soft brand glow */
    .bg-white.border {
        border-width: 2px;
        border-bottom-width: 7px;
        border-color: #e8eef3;
        background-image: linear-gradient(177deg, #ffffff 0%, #f5fbff 100%);
        box-shadow: 0 10px 24px -16px rgba(28, 176, 246, .45);
        transition: transform .14s ease, box-shadow .14s ease;
    }
    /* Clickable cards lift toward you on hover */
    a.bg-white.border:hover { transform: translateY(-3px); box-shadow: 0 18px 30px -14px rgba(28, 176, 246, .5); }

    /* ── Dark mode (class-based, applied via .dark on <html>) ── */
    .dark body { background: #0f172a; color: #e2e8f0; }
    .dark .bg-white { background: #1e293b !important; }
    .dark .bg-slate-50 { background: #0f172a !important; }
    .dark .bg-slate-100 { background: #334155 !important; }
    .dark .text-slate-800, .dark .text-slate-700 { color: #e2e8f0 !important; }
    .dark .text-slate-600, .dark .text-slate-500, .dark .text-slate-400 { color: #94a3b8 !important; }
    .dark .border-slate-200, .dark .border-slate-100, .dark .border-slate-300 { border-color: #334155 !important; }
    .dark .bg-white.border { border-color: #334155 !important; }
    .dark .divide-slate-100 > :not([hidden]) ~ :not([hidden]) { border-color: #334155 !important; }
    .dark input, .dark select, .dark textarea { background: #0f172a !important; color: #e2e8f0 !important; border-color: #334155 !important; }
    .dark .bg-brand-light { background: #0c4a6e !important; }
    .dark .text-brand-dark { color: #7dd3fc !important; }
</style>
<script>
    (function () { try { if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark'); } catch (e) {} })();
</script>
