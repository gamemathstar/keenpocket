@extends('layouts.app')
@section('title', 'Insights')
@section('heading', 'My Insights')

@section('content')
    {{-- Hero --}}
    <section class="bg-brand-light rounded-[2rem] border-b-8 border-brand p-6 sm:p-7 mb-8 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-dark">My insights 📊</h2>
            <p class="text-slate-600 mt-1">Your saving at a glance across every pocket and adashi.</p>
        </div>
        <x-mascot :size="96" class="hidden sm:block drop-shadow-xl" />
    </section>

    {{-- Headline money stats --}}
    <div class="grid sm:grid-cols-3 gap-4 sm:gap-6 mb-6">
        @php
            $money = [
                ['💰', 'Total saved', '₦'.number_format($stats['total_saved']), 'verified contributions, all groups', 'bg-sky-100', 'text-brand-dark'],
                ['📅', 'Saved in '.date('Y'), '₦'.number_format($stats['this_year']), 'this year so far', 'bg-emerald-100', 'text-slate-800'],
                ['🤲', 'Donated (Sadaqah)', '₦'.number_format($stats['donated']), 'your private total', 'bg-amber-100', 'text-amber-600'],
            ];
        @endphp
        @foreach ($money as [$icon, $label, $value, $sub, $tone, $valColor])
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="{{ $tone }} rounded-2xl h-11 w-11 flex items-center justify-center text-xl">{{ $icon }}</div>
                    <div class="text-xs text-slate-500">{{ $label }}</div>
                </div>
                <div class="text-3xl font-extrabold mt-3 {{ $valColor }}">{{ $value }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $sub }}</div>
            </div>
        @endforeach
    </div>

    {{-- Count stats --}}
    <div class="grid grid-cols-3 gap-4 sm:gap-6">
        @php
            $counts = [
                ['🧾', $stats['contributions'], 'contributions made'],
                ['👛', $stats['pockets'], 'pockets'],
                ['🔄', $stats['adashis'], 'adashi groups'],
            ];
        @endphp
        @foreach ($counts as [$icon, $value, $label])
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 text-center">
                <div class="text-2xl">{{ $icon }}</div>
                <div class="text-3xl font-extrabold mt-1">{{ $value }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    <p class="text-xs text-slate-400 mt-6">Totals reflect verified (admin-confirmed) contributions only. KeenPocket keeps the records — it never holds your money.</p>
@endsection
