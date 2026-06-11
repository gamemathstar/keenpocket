@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-semibold">Hello, {{ explode(' ', $user->name)[0] }} 👋</h2>
            <p class="text-slate-500 text-sm">Here's your savings at a glance.</p>
        </div>
        <div class="text-right">
            <div class="text-xs text-slate-500">Total saved</div>
            <div class="text-3xl font-bold text-brand-dark">₦{{ number_format($totalSaved) }}</div>
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-stat-tile icon="👛" :value="$pockets->count()" label="Active pockets" tone="blue" />
        <x-stat-tile icon="🔄" :value="$adashis->count()" label="Adashi groups" tone="purple" />
        <x-stat-tile icon="⭐" :value="$rep['band'] ?? 'New'" label="Reputation" tone="amber" />
        <x-stat-tile icon="💳" :value="is_null($walletBalance) ? '—' : '₦'.number_format($walletBalance)" label="Wallet" tone="green" />
    </div>

    {{-- Contribution trend --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-8">
        <h3 class="font-semibold mb-3">Contributions (last 6 months)</h3>
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
        <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-8 flex flex-wrap items-center gap-6">
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

    {{-- Pockets --}}
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">My Pockets</h3>
        <a href="{{ route('pockets.create') }}" class="text-sm bg-brand hover:bg-brand-dark text-white rounded-lg px-3 py-1.5">+ New pocket</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @forelse ($pockets as $p)
            <a href="{{ route('pockets.show', $p->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:shadow-sm hover:border-brand transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-brand-light text-brand-dark">{{ $p->pocket_type }}</span>
                    <span class="text-xs {{ $p->status ? 'text-emerald-600' : 'text-slate-400' }}">{{ $p->status ? 'Open' : 'Closed' }}</span>
                </div>
                <div class="font-semibold truncate">{{ $p->title }}</div>
                <div class="text-sm text-slate-500 mt-1">₦{{ number_format($p->amount_per_hand) }}/hand · {{ (int) $p->hand_count }} hand(s)</div>
                <div class="text-xs text-slate-400 mt-2">{{ $p->month_count }} months · {{ $p->year }}</div>
            </a>
        @empty
            <div class="col-span-full">
                <x-empty-state title="No pockets yet"
                    message="Join an open pocket or start your own to begin saving with your circle."
                    :action="route('discover')" actionLabel="Discover pockets" />
            </div>
        @endforelse
    </div>

    {{-- Adashi --}}
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">My Adashi</h3>
        <a href="{{ route('adashi.create') }}" class="text-sm bg-brand hover:bg-brand-dark text-white rounded-lg px-3 py-1.5">+ New adashi</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($adashis as $a)
            <a href="{{ route('adashi.show', $a->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:shadow-sm hover:border-brand transition">
                <div class="font-semibold truncate">{{ $a->name }}</div>
                <div class="text-sm text-slate-500 mt-1">₦{{ number_format($a->amount_per_cycle) }}/cycle · {{ $a->total_members }} members</div>
                <div class="text-xs text-slate-400 mt-2">Cycle {{ $a->current_cycle_number }} · {{ ucfirst(strtolower($a->status)) }}</div>
            </a>
        @empty
            <div class="col-span-full">
                <x-empty-state title="No adashi groups yet"
                    message="Create a rotating savings group and invite your people."
                    :action="route('adashi.create')" actionLabel="Start an adashi" />
            </div>
        @endforelse
    </div>
@endsection
