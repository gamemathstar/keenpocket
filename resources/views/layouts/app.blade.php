<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/keenpocket-icon.svg') }}">
    <title>@yield('title', 'KeenPocket')</title>
    @include('partials.styles')
</head>
<body class="h-full text-slate-800">
<div class="min-h-full md:flex">
    {{-- Sidebar --}}
    <aside class="md:w-64 md:flex md:flex-col bg-white border-b md:border-b-0 md:border-r border-slate-200">
        <div class="px-5 py-4 flex items-center justify-between border-b border-slate-100">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/keenpocket-icon.svg') }}" alt="" class="h-8 w-8 rounded-lg">
                <span class="font-semibold text-lg">KeenPocket</span>
            </a>
            <button type="button" onclick="document.getElementById('navPanel').classList.toggle('hidden')"
                    class="md:hidden h-9 w-9 inline-flex items-center justify-center rounded-lg hover:bg-slate-100 text-xl" aria-label="Toggle menu">☰</button>
        </div>

        <div id="navPanel" class="hidden md:flex md:flex-col md:flex-1">
            <nav class="p-3 md:flex-1 space-y-1">
                @php
                    $me = auth()->user();
                    $isActive = fn ($r) => request()->routeIs($r) || request()->routeIs(str_replace('.index', '', $r).'.*');
                    $pocketItems = [['pockets.index', 'My Pockets', '👛'], ['adashi.index', 'Adashi', '🔄']];
                    $profileItems = [
                        ['profile', 'Profile', '⭐'],
                        ['friends.index', 'Friends & Invites', '👥'],
                        ['wallet.index', 'Wallet', '💳'],
                        ['payouts.index', 'Payouts & Bank', '🏦'],
                        ['guarantor.requests', 'Vouches', '🤝'],
                        ['insights', 'Insights', '📊'],
                        ['admin.health', 'Admin', '🩺'],
                    ];
                    $pocketOpen = collect($pocketItems)->contains(fn ($i) => $isActive($i[0]));
                    $profileOpen = collect($profileItems)->contains(fn ($i) => $isActive($i[0]));
                @endphp

                @include('partials.nav-link', ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '🏠'])

                {{-- Pocket (opens to tabbed pages) --}}
                <a href="{{ route('pockets.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-extrabold uppercase tracking-wide border-2 {{ $pocketOpen ? 'bg-brand-light text-brand-dark border-brand/40' : 'text-slate-500 border-transparent hover:bg-slate-100' }}">
                    <span class="text-base">👛</span><span>Pocket</span>
                </a>

                {{-- School (right after Pocket) --}}
                @if (config('school.enabled', true) && $me->canCreateSchool())
                    @include('partials.nav-link', ['route' => 'school.create', 'label' => 'My School', 'icon' => '🏫'])
                @endif
                @if ($me->children()->exists())
                    @include('partials.nav-link', ['route' => 'school.children', 'label' => 'My Children', 'icon' => '🎒'])
                @endif

                @include('partials.nav-link', ['route' => 'plans.index', 'label' => 'Shopping', 'icon' => '🛒'])
                @include('partials.nav-link', ['route' => 'discover', 'label' => 'Discover', 'icon' => '🧭'])
                @include('partials.nav-link', ['route' => 'leaderboard', 'label' => 'Leaderboard', 'icon' => '🏆'])

                @if ($me->isSuperAdmin())
                    @include('partials.nav-link', ['route' => 'super-admin.index', 'label' => 'Super Admin', 'icon' => '🛡️'])
                @endif

                {{-- Profile (opens to tabbed pages) --}}
                <a href="{{ route('profile') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-extrabold uppercase tracking-wide border-2 {{ $profileOpen ? 'bg-brand-light text-brand-dark border-brand/40' : 'text-slate-500 border-transparent hover:bg-slate-100' }}">
                    <span class="text-base">⭐</span><span>Profile</span>
                </a>
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
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-1" title="Your Keens balance">🪙 {{ number_format(auth()->user()->keens ?? 0) }}</span>
                <button type="button" onclick="(function(){var d=document.documentElement.classList.toggle('dark');try{localStorage.setItem('theme',d?'dark':'light');}catch(e){}})()" class="text-xl" title="Toggle dark mode">🌓</button>
                <a href="{{ route('settings') }}" class="text-xl" title="Settings">⚙️</a>
                <span class="text-slate-500 hidden sm:inline">{{ auth()->user()->name ?? '' }}</span>
                <a href="{{ route('settings') }}"><x-avatar :user="auth()->user()" :size="32" /></a>
            </div>
        </header>

        {{-- Group tabs: shown when the current page belongs to the Pocket or Profile group --}}
        @php
            $groupTabs = collect($pocketItems)->contains(fn ($i) => $isActive($i[0])) ? $pocketItems
                : (collect($profileItems)->contains(fn ($i) => $isActive($i[0])) ? $profileItems : null);
        @endphp
        @if ($groupTabs)
            <nav class="bg-white border-b border-slate-200 px-4 sm:px-6 overflow-x-auto">
                <div class="flex gap-1">
                    @foreach ($groupTabs as [$route, $label, $icon])
                        @php $on = $isActive($route); @endphp
                        <a href="{{ route($route) }}" class="flex items-center gap-1.5 px-3 py-3 text-sm font-bold border-b-2 whitespace-nowrap transition-colors {{ $on ? 'border-brand text-brand-dark' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                            <span>{{ $icon }}</span> {{ $label }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        <main class="p-6 pb-24 md:pb-6 flex-1">
            @if (session('status'))
                <div id="toast" class="fixed top-5 right-5 z-50 bg-white border border-slate-200 rounded-xl pl-3 pr-4 py-3 shadow-lg text-sm font-extrabold flex items-center gap-2.5">
                    <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                    <span>{{ session('status') }}</span>
                </div>
                <script>setTimeout(function(){var t=document.getElementById('toast');if(t){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(function(){t.remove();},400);}}, 3500);</script>
            @endif
            @if (session('celebrate'))
                <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
                <img id="celebMascot" src="{{ asset('ant-k/kandfriendsceleb.png') }}" alt=""
                     class="fixed left-1/2 bottom-0 z-50 w-80 max-w-[80vw] pointer-events-none drop-shadow-2xl"
                     style="transform: translate(-50%, 110%); opacity: 0; transition: transform .45s cubic-bezier(.2,.8,.3,1.2), opacity .45s;">
                <script>
                    window.addEventListener('load', function () {
                        if (window.confetti) {
                            confetti({ particleCount: 130, spread: 75, origin: { y: 0.6 }, colors: ['#1cb0f6','#1899d6','#ddf4ff','#ffd900'] });
                        }
                        var k = document.getElementById('celebMascot');
                        if (k) {
                            requestAnimationFrame(function () { k.style.transform = 'translate(-50%, 6%)'; k.style.opacity = '1'; });
                            setTimeout(function () { k.style.transform = 'translate(-50%, 110%)'; k.style.opacity = '0'; }, 3200);
                            setTimeout(function () { k.remove(); }, 3700);
                        }
                    });
                </script>
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

{{-- Mobile bottom tab bar (Duolingo-style). Pocket/Profile open to their tabbed pages. --}}
<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t-2 border-slate-200 grid grid-cols-5">
    @php
        $tabs = [
            ['dashboard', 'Home', '🏠', null],
            ['pockets.index', 'Pocket', '👛', 'adashi.*'],
            ['plans.index', 'Shopping', '🛒', null],
            ['discover', 'Discover', '🧭', null],
            ['profile', 'Profile', '⭐', null],
        ];
    @endphp
    @foreach ($tabs as [$route, $label, $icon, $extra])
        @php $active = request()->routeIs($route) || request()->routeIs(str_replace('.index', '', $route).'.*') || ($extra && request()->routeIs($extra)); @endphp
        <a href="{{ route($route) }}" class="flex flex-col items-center gap-0.5 py-2 text-[10px] font-extrabold uppercase {{ $active ? 'text-brand-dark' : 'text-slate-400' }}">
            <span class="text-xl">{{ $icon }}</span>{{ $label }}
        </a>
    @endforeach
</nav>

<script>
    // Copy-to-clipboard helper used by account numbers etc.
    function kpCopy(text, btn) {
        var done = function () {
            if (!btn) return;
            var old = btn.innerHTML;
            btn.innerHTML = '✓';
            setTimeout(function () { btn.innerHTML = old; }, 1200);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, done);
        } else {
            var t = document.createElement('textarea');
            t.value = text; document.body.appendChild(t); t.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(t); done();
        }
    }
</script>
</body>
</html>
