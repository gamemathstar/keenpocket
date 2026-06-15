@extends('layouts.app')
@section('title', 'Shopping')
@section('heading', 'Shopping')

@section('content')
    @php $svc = app(\App\Services\Plan\PlanService::class); @endphp

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-brand-light rounded-[2rem] border-b-8 border-brand p-6 sm:p-8 mb-8 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-dark leading-tight">Shopping cockpit 🛒</h2>
            <p class="text-slate-600 mt-1">Your grocery strategy — budget ahead, tick off what you buy, and carry over what you defer.</p>
            <a href="{{ route('plans.create') }}" class="inline-block mt-4 rounded-xl bg-brand hover:bg-brand-dark text-white font-bold px-5 py-2.5">+ New plan</a>
        </div>
        <img src="{{ asset('ant-k/kplanning.png') }}" alt="" class="hidden sm:block h-28 w-auto object-contain rounded-2xl shrink-0 drop-shadow-xl">
    </section>

    @php $sections = ['Your plans' => $owned, 'Shared with you' => $shared]; @endphp
    @foreach ($sections as $heading => $plans)
        @if ($plans->isNotEmpty())
            <h3 class="font-extrabold text-slate-700 mb-4">{{ $heading }}</h3>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                @foreach ($plans as $plan)
                    @php $s = $svc->summary($plan); @endphp
                    <a href="{{ route('plans.show', $plan->id) }}" class="card-depth block bg-white rounded-[1.5rem] border-2 border-slate-100 hover:border-brand p-5">
                        <div class="flex items-start gap-3 mb-3">
                            <div class="bg-amber-100 rounded-2xl h-11 w-11 shrink-0 flex items-center justify-center text-xl">🛒</div>
                            <div class="min-w-0 flex-1">
                                <div class="font-extrabold truncate">{{ $plan->title }}</div>
                                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $plan->periodLabel() }}@if ($plan->status === 'ARCHIVED') · archived @endif</div>
                            </div>
                        </div>

                        @if ($s['budget'] > 0)
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-slate-400 uppercase tracking-wide">Budget</span>
                                <span class="text-brand-dark">₦{{ number_format($s['spent']) }} / ₦{{ number_format($s['budget']) }}</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden mb-3"><div class="h-full {{ $s['over_budget'] ? 'bg-amber-500' : 'bg-brand' }}" style="width: {{ $s['percent_spent'] }}%"></div></div>
                        @endif

                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-xl bg-emerald-50 py-2"><div class="font-extrabold text-emerald-700">{{ $s['purchased'] }}</div><div class="text-[10px] text-slate-400 font-bold uppercase">Bought</div></div>
                            <div class="rounded-xl bg-slate-50 py-2"><div class="font-extrabold text-slate-700">{{ $s['pending'] }}</div><div class="text-[10px] text-slate-400 font-bold uppercase">Pending</div></div>
                            <div class="rounded-xl bg-amber-50 py-2"><div class="font-extrabold text-amber-700">{{ $s['deferred'] }}</div><div class="text-[10px] text-slate-400 font-bold uppercase">Deferred</div></div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @endforeach

    @if ($owned->isEmpty() && $shared->isEmpty())
        <div class="bg-white rounded-[1.5rem] border-2 border-dashed border-slate-300 p-10 text-center">
            <div class="text-4xl mb-2">🧾</div>
            <h3 class="font-extrabold">No plans yet</h3>
            <p class="text-sm text-slate-500 mt-1 mb-4">Create your first monthly grocery plan and share it with your spouse.</p>
            <a href="{{ route('plans.create') }}" class="inline-block rounded-xl bg-brand hover:bg-brand-dark text-white font-bold px-5 py-2.5">Create a plan</a>
        </div>
    @endif
@endsection
