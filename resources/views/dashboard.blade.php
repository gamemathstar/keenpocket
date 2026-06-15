@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-brand-light rounded-[2rem] border-b-8 border-brand p-6 sm:p-8 mb-8 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-dark leading-tight">Hello, {{ explode(' ', $user->name)[0] }} 👋</h2>
            <p class="text-slate-600 mt-1 sm:text-lg">You've saved <span class="font-extrabold text-brand-dark">₦{{ number_format($totalSaved) }}</span> so far. Keep it up!</p>
        </div>
        <x-mascot :size="120" class="shrink-0 hidden sm:block kp-hero-bounce drop-shadow-xl" />
    </section>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        @php
            $stats = [
                ['👛', $pockets->count(), 'Active pockets', 'bg-sky-100'],
                ['🔄', $adashis->count(), 'Adashi groups', 'bg-violet-100'],
                ['⭐', $rep['band'] ?? 'New', 'Reputation', 'bg-amber-100'],
                ['💳', is_null($walletBalance) ? '—' : '₦'.number_format($walletBalance), 'Wallet', 'bg-emerald-100'],
            ];
        @endphp
        @foreach ($stats as [$icon, $value, $label, $tone])
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
                <div class="{{ $tone }} rounded-2xl h-12 w-12 shrink-0 flex items-center justify-center text-2xl">{{ $icon }}</div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 truncate">{{ $label }}</p>
                    <p class="text-xl sm:text-2xl font-extrabold text-slate-800 truncate">{{ $value }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Weekly goal / streak --}}
    <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 sm:p-6 mb-8 flex flex-col sm:flex-row items-center justify-between gap-5">
        <div class="flex items-center gap-4">
            <div class="h-14 w-14 shrink-0 rounded-full bg-amber-100 flex items-center justify-center text-3xl">{{ $thisWeekMet ? '✅' : '🔥' }}</div>
            <div>
                <h3 class="font-extrabold text-lg">{{ $weekStreak }}-week streak</h3>
                <p class="text-sm text-slate-500">{{ $thisWeekMet ? "Done — you've contributed this week! 🎉" : 'Contribute this week to keep it going.' }} · 🧊 {{ $streakFreezes }} freeze(s) left</p>
            </div>
        </div>
        <div class="w-full sm:w-1/3 bg-slate-100 h-4 rounded-full overflow-hidden">
            <div class="bg-brand h-full rounded-full" style="width: {{ $thisWeekMet ? 100 : 50 }}%"></div>
        </div>
    </div>

    {{-- Contribution trend --}}
    <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 sm:p-6 mb-8">
        <h3 class="font-extrabold text-lg mb-4">Contribution trend <span class="text-slate-400 font-semibold text-sm">· last 6 months</span></h3>
        @if (array_sum($chartData) > 0)
            <canvas id="contribChart" height="90"></canvas>
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
            <script>
                (function () {
                    var ctx = document.getElementById('contribChart');
                    if (!ctx || !window.Chart) return;
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: @json($chartLabels),
                            datasets: [{
                                label: 'Paid (₦)',
                                data: @json($chartData),
                                backgroundColor: '#1cb0f6',
                                borderRadius: 6,
                            }]
                        },
                        options: {
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return '₦' + v.toLocaleString(); } } } }
                        }
                    });
                })();
            </script>
        @else
            <p class="text-sm text-slate-500">No contributions recorded yet — they'll appear here as you pay.</p>
        @endif
    </div>

    @if ($profile)
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 sm:p-6 mb-8 flex flex-wrap items-center gap-6">
            <x-progress-ring :percent="$rep['score']" :label="$rep['score']" sublabel="reputation" />
            <div class="flex-1 min-w-[200px]">
                <div class="text-xs text-slate-500 uppercase font-bold tracking-wide">Savings streak</div>
                <div class="text-3xl font-extrabold">{{ $profile['streak'] }} 🔥</div>
                @php $earned = collect($profile['badges'])->where('earned', true); @endphp
                @if ($earned->count())
                    <div class="flex flex-wrap gap-2 mt-3">
                        @foreach ($earned->take(6) as $b)
                            <span class="bg-brand-light text-brand-dark rounded-full px-3 py-1 text-xs font-bold">🏅 {{ $b['label'] }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500 mt-2">Earn badges by joining and paying on time.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Parallel columns: Pockets | Adashi --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
        {{-- My Pockets --}}
        <section>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-extrabold">My Pockets</h3>
                <a href="{{ route('pockets.index') }}" class="text-sm font-bold text-brand-dark hover:underline">View all</a>
            </div>
            <div class="space-y-4">
                @forelse ($pockets as $p)
                    <a href="{{ route('pockets.show', $p->id) }}" class="card-depth block bg-white rounded-[1.5rem] border-2 border-slate-100 hover:border-brand p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="bg-sky-100 rounded-2xl h-11 w-11 flex items-center justify-center text-xl">👛</div>
                            <span class="text-[11px] font-bold uppercase tracking-wide rounded-full px-2.5 py-1 {{ $p->status ? 'bg-brand-light text-brand-dark' : 'bg-slate-100 text-slate-500' }}">{{ $p->status ? 'Open' : 'Closed' }}</span>
                        </div>
                        <div class="font-extrabold truncate">{{ $p->title }}</div>
                        <div class="flex justify-between text-sm text-slate-500 font-semibold mt-1">
                            <span>₦{{ number_format($p->amount_per_hand) }}/hand · {{ (int) $p->hand_count }} hand(s)</span>
                            <span>{{ $p->month_count }} months · {{ $p->year }}</span>
                        </div>
                    </a>
                @empty
                    <x-empty-state title="No pockets yet"
                        message="Join an open pocket or start your own to begin saving with your circle."
                        :action="route('discover')" actionLabel="Discover pockets" />
                @endforelse
            </div>
        </section>

        {{-- My Adashi --}}
        <section>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-extrabold">My Adashi</h3>
                <a href="{{ route('adashi.index') }}" class="text-sm font-bold text-brand-dark hover:underline">View all</a>
            </div>
            <div class="space-y-4">
                @forelse ($adashis as $a)
                    <a href="{{ route('adashi.show', $a->id) }}" class="card-depth block bg-white rounded-[1.5rem] border-2 border-slate-100 hover:border-brand p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="bg-amber-100 rounded-2xl h-11 w-11 flex items-center justify-center text-xl">🔄</div>
                            <span class="text-[11px] font-bold uppercase tracking-wide rounded-full px-2.5 py-1 bg-amber-100 text-amber-700">{{ ucfirst(strtolower($a->status)) }}</span>
                        </div>
                        <div class="font-extrabold truncate">{{ $a->name }}</div>
                        <div class="flex justify-between text-sm text-slate-500 font-semibold mt-1">
                            <span>Cycle {{ $a->current_cycle_number }}</span>
                            <span>₦{{ number_format($a->amount_per_cycle) }}/cycle · {{ $a->total_members }} members</span>
                        </div>
                    </a>
                @empty
                    <x-empty-state title="No adashi groups yet"
                        message="Create a rotating savings group and invite your people."
                        :action="route('adashi.create')" actionLabel="Start an adashi" />
                @endforelse
            </div>
        </section>
    </div>
@endsection
