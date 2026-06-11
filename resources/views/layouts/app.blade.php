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
    <aside class="md:w-64 md:flex md:flex-col bg-white border-r border-slate-200">
        <div class="px-6 py-5 flex items-center gap-2 border-b border-slate-100">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand text-white font-bold">K</span>
            <span class="font-semibold text-lg">KeenPocket</span>
        </div>
        <nav class="p-3 md:flex-1 flex md:block gap-1 overflow-x-auto">
            @php
                $nav = [
                    ['dashboard', 'Dashboard', '🏠'],
                    ['pockets.index', 'My Pockets', '👛'],
                    ['adashi.index', 'Adashi', '🔄'],
                    ['discover', 'Discover', '🧭'],
                    ['wallet.index', 'Wallet', '💳'],
                    ['payouts.index', 'Payouts & Bank', '🏦'],
                    ['referrals.index', 'Referrals', '🎁'],
                    ['profile', 'Profile', '⭐'],
                ];
            @endphp
            @foreach ($nav as [$route, $label, $icon])
                @php $active = request()->routeIs($route) || request()->routeIs(str_replace('.index','',$route).'.*'); @endphp
                <a href="{{ route($route) }}"
                   class="whitespace-nowrap flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $active ? 'bg-brand-light text-brand-dark' : 'text-slate-600 hover:bg-slate-100' }}">
                    <span>{{ $icon }}</span><span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>
        <div class="hidden md:block p-3 border-t border-slate-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">↩︎ Log out</button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h1 class="text-lg font-semibold">@yield('heading', 'KeenPocket')</h1>
            <div class="flex items-center gap-4 text-sm">
                @php $unread = \App\Models\Notification::where('user_id', auth()->id())->where('status', 'Not Read')->count(); @endphp
                <a href="{{ route('notifications.index') }}" class="relative" title="Notifications">
                    <span class="text-xl">🔔</span>
                    @if ($unread)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>
                <span class="text-slate-500 hidden sm:inline">{{ auth()->user()->name ?? '' }}</span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-light text-brand-dark font-semibold">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </span>
            </div>
        </header>

        <main class="p-6 flex-1">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('status') }}</div>
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
</body>
</html>
