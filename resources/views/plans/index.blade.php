@extends('layouts.app')
@section('title', 'Home Planning')
@section('heading', 'Home Planning')

@section('content')
    @php $svc = app(\App\Services\Plan\PlanService::class); @endphp
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-slate-500">Plan your monthly groceries — budget ahead, tick off what you buy, and carry over what you defer.</p>
        <a href="{{ route('plans.create') }}" class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">+ New plan</a>
    </div>

    @php
        $sections = ['Your plans' => $owned, 'Shared with you' => $shared];
    @endphp
    @foreach ($sections as $heading => $plans)
        @if ($plans->isNotEmpty())
            <h3 class="font-semibold text-slate-700 mb-3">{{ $heading }}</h3>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                @foreach ($plans as $plan)
                    @php $s = $svc->summary($plan); @endphp
                    <a href="{{ route('plans.show', $plan->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:shadow-sm hover:border-brand transition">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold truncate">{{ $plan->title }}</span>
                            @if ($plan->status === 'ARCHIVED')<span class="text-xs text-slate-400">archived</span>@endif
                        </div>
                        <div class="text-xs text-slate-400 mb-3">{{ $plan->month ?: 'No month set' }}</div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">{{ $s['purchased'] }} bought</span>
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $s['pending'] }} to buy</span>
                            @if ($s['deferred'])<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $s['deferred'] }} deferred</span>@endif
                        </div>
                        @if ($s['budget'] > 0)
                            <div class="mt-3 text-xs text-slate-500">₦{{ number_format($s['spent']) }} of ₦{{ number_format($s['budget']) }}</div>
                            <div class="h-2 rounded-full bg-slate-100 mt-1 overflow-hidden"><div class="h-full {{ $s['over_budget'] ? 'bg-amber-500' : 'bg-brand' }}" style="width: {{ $s['percent_spent'] }}%"></div></div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    @endforeach

    @if ($owned->isEmpty() && $shared->isEmpty())
        <div class="bg-white rounded-xl border border-dashed border-slate-300 p-10 text-center">
            <div class="text-4xl mb-2">🧾</div>
            <h3 class="font-semibold">No plans yet</h3>
            <p class="text-sm text-slate-500 mt-1 mb-4">Create your first monthly grocery plan and share it with your spouse.</p>
            <a href="{{ route('plans.create') }}" class="inline-block rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Create a plan</a>
        </div>
    @endif
@endsection
