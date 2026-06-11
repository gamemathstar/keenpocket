<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KeenPocket')</title>
    @include('partials.styles')
</head>
<body class="h-full text-slate-800">
<div class="min-h-full md:flex">
    {{-- Sidebar --}}
    <aside class="md:w-64 md:flex md:flex-col bg-white border-b md:border-b-0 md:border-r border-slate-200">
        <div class="px-5 py-4 flex items-center justify-between border-b border-slate-100">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand text-white font-bold">K</span>
                <span class="font-semibold text-lg">KeenPocket</span>
            </a>
            <button type="button" onclick="document.getElementById('navPanel').classList.toggle('hidden')"
                    class="md:hidden h-9 w-9 inline-flex items-center justify-center rounded-lg hover:bg-slate-100 text-xl" aria-label="Toggle menu">☰</button>
        </div>

        <div id="navPanel" class="hidden md:flex md:flex-col md:flex-1">
            <nav class="p-3 md:flex-1 space-y-1">
                @php
                    $nav = [
                        ['dashboard', 'Dashboard', '🏠'],
                        ['pockets.index', 'My Pockets', '👛'],
                        ['adashi.index', 'Adashi', '🔄'],
                        ['discover', 'Discover', '🧭'],
                        ['leaderboard', 'Leaderboard', '🏆'],
                        ['wallet.index', 'Wallet', '💳'],
                        ['payouts.index', 'Payouts & Bank', '🏦'],
                        ['referrals.index', 'Referrals', '🎁'],
                        ['profile', 'Profile', '⭐'],
                    ];
                @endphp
                @foreach ($nav as [$route, $label, $icon])
                    @php $active = request()->routeIs($route) || request()->routeIs(str_replace('.index','',$route).'.*'); @endphp
                    <a href="{{ route($route) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-extrabold uppercase tracking-wide border-2 {{ $active ? 'bg-brand-light text-brand-dark border-brand/40' : 'text-slate-500 border-transparent hover:bg-slate-100' }}">
                        <span class="text-base">{{ $icon }}</span><span>{{ $label }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="p-3 border-t border-slate-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full text-left px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">↩︎ Log out</button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h1 class="text-lg font-semibold">@yield('heading', 'KeenPocket')</h1>
            <form method="GET" action="{{ route('search') }}" class="hidden md:block flex-1 max-w-xs mx-6">
                <input name="q" value="{{ request('q') }}" placeholder="Search pockets & adashi…"
                       class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-brand focus:ring-brand">
            </form>
            <div class="flex items-center gap-4 text-sm">
                @php $unread = \App\Models\Notification::where('user_id', auth()->id())->where('status', 'Not Read')->count(); @endphp
                <a href="{{ route('notifications.index') }}" class="relative" title="Notifications">
                    <span class="text-xl">🔔</span>
                    @if ($unread)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>
                <a href="{{ route('settings') }}" class="text-xl" title="Settings">⚙️</a>
                <span class="text-slate-500 hidden sm:inline">{{ auth()->user()->name ?? '' }}</span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-light text-brand-dark font-semibold">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </span>
            </div>
        </header>

        <main class="p-6 pb-24 md:pb-6 flex-1">
            @if (session('status'))
                <div id="toast" class="fixed top-5 right-5 z-50 bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-lg text-sm font-extrabold flex items-center gap-2">
                    <span>✅</span><span>{{ session('status') }}</span>
                </div>
                <script>setTimeout(function(){var t=document.getElementById('toast');if(t){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(function(){t.remove();},400);}}, 3500);</script>
            @endif
            @if (session('celebrate'))
                <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
                <script>window.addEventListener('load',function(){if(window.confetti){confetti({particleCount:130,spread:75,origin:{y:0.6},colors:['#1cb0f6','#1899d6','#ddf4ff','#ffd900']});}});</script>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

{{-- Mobile bottom tab bar (Duolingo-style) --}}
<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t-2 border-slate-200 grid grid-cols-5">
    @php
        $tabs = [
            ['dashboard', 'Home', '🏠'],
            ['pockets.index', 'Pockets', '👛'],
            ['adashi.index', 'Adashi', '🔄'],
            ['discover', 'Discover', '🧭'],
            ['profile', 'Profile', '⭐'],
        ];
    @endphp
    @foreach ($tabs as [$route, $label, $icon])
        @php $active = request()->routeIs($route) || request()->routeIs(str_replace('.index','',$route).'.*'); @endphp
        <a href="{{ route($route) }}" class="flex flex-col items-center gap-0.5 py-2 text-[10px] font-extrabold uppercase {{ $active ? 'text-brand-dark' : 'text-slate-400' }}">
            <span class="text-xl">{{ $icon }}</span>{{ $label }}
        </a>
    @endforeach
</nav>
</body>
</html>
