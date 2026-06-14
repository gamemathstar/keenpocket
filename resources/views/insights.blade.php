@extends('layouts.app')
@section('title', 'Insights')
@section('heading', 'My Insights')

@section('content')
    <p class="text-sm text-slate-500 mb-6">Your saving at a glance across every pocket and adashi.</p>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500">Total saved</div>
            <div class="text-3xl font-extrabold text-brand-dark mt-1">₦{{ number_format($stats['total_saved']) }}</div>
            <div class="text-xs text-slate-400 mt-1">verified contributions, all groups</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500">Saved in {{ date('Y') }}</div>
            <div class="text-3xl font-extrabold mt-1">₦{{ number_format($stats['this_year']) }}</div>
            <div class="text-xs text-slate-400 mt-1">this year so far</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500">Donated (Sadaqah)</div>
            <div class="text-3xl font-extrabold text-amber-600 mt-1">₦{{ number_format($stats['donated']) }}</div>
            <div class="text-xs text-slate-400 mt-1">your private total</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 text-center">
            <div class="text-3xl font-extrabold">{{ $stats['contributions'] }}</div>
            <div class="text-xs text-slate-500 mt-1">contributions made</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 text-center">
            <div class="text-3xl font-extrabold">{{ $stats['pockets'] }}</div>
            <div class="text-xs text-slate-500 mt-1">pockets</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 text-center">
            <div class="text-3xl font-extrabold">{{ $stats['adashis'] }}</div>
            <div class="text-xs text-slate-500 mt-1">adashi groups</div>
        </div>
    </div>

    <p class="text-xs text-slate-400 mt-6">Totals reflect verified (admin-confirmed) contributions only. KeenPocket keeps the records — it never holds your money.</p>
@endsection
