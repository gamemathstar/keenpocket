@extends('layouts.app')
@section('title', 'My Children')
@section('heading', 'School fees')

@section('content')
    <p class="text-sm text-slate-500 mb-6">What you've paid and what's left, per child and term. Payments are recorded by the school.</p>

    @forelse ($rows as $r)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="font-semibold">{{ $r->student->name }}</h3>
                    <p class="text-xs text-slate-400">{{ $r->school }}@if($r->class) · {{ $r->class }}@endif</p>
                </div>
                @if ($r->plan)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-brand-light text-brand-dark">
                        Plan: {{ $r->plan->mode === 'min_monthly' ? '₦'.number_format($r->plan->min_monthly).'/mo' : $r->plan->percent.'% per term' }}
                    </span>
                @endif
            </div>
            <div class="grid sm:grid-cols-3 gap-3">
                @foreach ($r->terms as $t => $info)
                    @php $pct = $info['fee'] > 0 ? min(100, (int) round($info['paid'] / $info['fee'] * 100)) : 0; @endphp
                    <div class="rounded-lg border border-slate-100 p-3">
                        <div class="flex justify-between text-sm"><span class="font-medium">Term {{ $t }}</span><span class="text-slate-400">{{ $pct }}%</span></div>
                        <div class="h-2 rounded-full bg-slate-100 mt-1 overflow-hidden"><div class="h-full {{ $info['pending'] == 0 && $info['fee'] > 0 ? 'bg-emerald-500' : 'bg-brand' }}" style="width: {{ $pct }}%"></div></div>
                        <div class="text-xs text-slate-500 mt-1">Paid ₦{{ number_format($info['paid']) }} of ₦{{ number_format($info['fee']) }}</div>
                        @if ($info['pending'] > 0)
                            <div class="text-xs text-amber-600 font-semibold">₦{{ number_format($info['pending']) }} pending</div>
                        @elseif ($info['fee'] > 0)
                            <div class="text-xs text-emerald-600 font-semibold">Fully paid ✓</div>
                        @else
                            <div class="text-xs text-slate-400">No fee set</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-500">
            No children linked to your account yet. Your school adds them.
        </div>
    @endforelse
@endsection
