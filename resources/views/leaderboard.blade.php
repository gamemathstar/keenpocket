@extends('layouts.app')
@section('title', 'Leaderboard')
@section('heading', 'Leaderboard')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 max-w-2xl">
        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-0.5 text-sm font-bold">
            <a href="{{ route('leaderboard', ['period' => 'week']) }}" class="px-4 py-1.5 rounded-lg {{ $period === 'week' ? 'bg-brand-light text-brand-dark' : 'text-slate-500' }}">This week</a>
            <a href="{{ route('leaderboard', ['period' => 'all']) }}" class="px-4 py-1.5 rounded-lg {{ $period === 'all' ? 'bg-brand-light text-brand-dark' : 'text-slate-500' }}">All time</a>
        </div>
        @if ($period === 'week')
            <span class="text-xs text-slate-400 font-bold uppercase tracking-wide">🔄 Resets every Monday</span>
        @endif
    </div>
    <p class="text-slate-500 text-sm mb-5">Most consistent savers, ranked by number of contributions. Keep paying on time to climb! 🏆 <span class="text-slate-400">(amounts stay private)</span></p>

    @if (count($rows))
        @if ($myStanding && $myStanding['rank'] > 20)
            <div class="bg-brand-light border border-brand/30 rounded-2xl p-4 mb-5 flex items-center justify-between max-w-2xl">
                <span class="font-extrabold text-brand-dark">Your rank: #{{ $myStanding['rank'] }}</span>
                <span class="font-extrabold">{{ number_format($myStanding['total']) }} contributions</span>
            </div>
        @endif

        {{-- Podium: top 3 --}}
        @if (count($rows) >= 3)
            @php $podium = [['r' => $rows[1], 'h' => 'h-20', 'medal' => '🥈', 'ring' => 'ring-slate-300'], ['r' => $rows[0], 'h' => 'h-28', 'medal' => '🥇', 'ring' => 'ring-amber-400'], ['r' => $rows[2], 'h' => 'h-16', 'medal' => '🥉', 'ring' => 'ring-orange-300']]; @endphp
            <div class="max-w-2xl grid grid-cols-3 gap-3 items-end mb-6">
                @foreach ($podium as $col)
                    @php $r = $col['r']; $first = $col['medal'] === '🥇'; @endphp
                    <div class="flex flex-col items-center">
                        <div class="text-2xl">{{ $col['medal'] }}</div>
                        <div class="rounded-full ring-4 {{ $col['ring'] }} mt-1"><x-avatar :user="$r['name']" :size="$first ? 60 : 44" /></div>
                        <a href="{{ route('users.show', $r['user_id']) }}" class="mt-2 font-extrabold text-sm text-center truncate max-w-full hover:underline">{{ $r['is_me'] ? 'You' : $r['name'] }}</a>
                        <div class="text-brand-dark font-extrabold text-sm">{{ number_format($r['total']) }} <span class="text-xs text-slate-400">pts</span></div>
                        <div class="w-full {{ $col['h'] }} mt-2 rounded-t-2xl card-depth border-2 border-slate-100 flex items-start justify-center pt-2 text-lg font-extrabold text-slate-400 {{ $first ? 'bg-brand-light' : 'bg-white' }}">#{{ $r['rank'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        @php $rest = count($rows) >= 3 ? array_slice($rows, 3) : $rows; @endphp
        @if (count($rest))
        <div class="max-w-2xl bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 divide-y divide-slate-100 overflow-hidden">
            @foreach ($rest as $r)
                <div class="flex items-center gap-4 px-4 py-3 {{ $r['is_me'] ? 'bg-brand-light/50' : '' }}">
                    <div class="w-8 text-center text-lg font-extrabold"><span class="text-slate-400">{{ $r['rank'] }}</span></div>
                    <x-avatar :user="$r['name']" :size="36" />
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('users.show', $r['user_id']) }}" class="font-bold truncate hover:underline">{{ $r['name'] }}</a>
                        @if ($r['is_me'])<span class="text-xs text-brand-dark font-bold ml-1">(you)</span>@endif
                    </div>
                    <span class="font-extrabold text-brand-dark">{{ number_format($r['total']) }} <span class="text-xs text-slate-400 font-bold">pts</span></span>
                </div>
            @endforeach
        </div>
        @endif
    @else
        <x-empty-state title="No rankings yet"
            message="Once contributions start rolling in, top savers will appear here." />
    @endif
@endsection
