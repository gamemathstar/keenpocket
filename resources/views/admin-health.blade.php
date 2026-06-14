@extends('layouts.app')
@section('title', 'Admin dashboard')
@section('heading', 'Admin dashboard')

@section('content')
    <p class="text-sm text-slate-500 mb-6">Health of the groups you run — collection progress and who still owes.</p>

    @if ($pockets->isEmpty() && $adashis->isEmpty())
        <div class="bg-white rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-500">
            You don't administer any pockets or adashis yet.
        </div>
    @endif

    @if ($pockets->isNotEmpty())
        <h3 class="font-semibold text-slate-700 mb-3">Pockets</h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            @foreach ($pockets as $row)
                <a href="{{ route('pockets.show', $row->pocket->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:border-brand transition">
                    <div class="font-semibold truncate">{{ $row->pocket->title }}</div>
                    <div class="text-xs text-slate-400 mb-2">{{ $row->members }} members</div>
                    <div class="flex justify-between text-xs text-slate-500 mb-1"><span>Collected</span><span>{{ $row->percent }}%</span></div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-brand" style="width: {{ $row->percent }}%"></div></div>
                    <div class="text-xs text-slate-400 mt-1">₦{{ number_format($row->collected) }} of ₦{{ number_format($row->target) }}</div>
                    @if ($row->at_risk)
                        <div class="text-xs text-amber-600 font-semibold mt-2">⚠️ {{ $row->at_risk }} member(s) yet to contribute</div>
                    @else
                        <div class="text-xs text-emerald-600 font-semibold mt-2">✓ everyone has contributed</div>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

    @if ($adashis->isNotEmpty())
        <h3 class="font-semibold text-slate-700 mb-3">Adashis</h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($adashis as $row)
                <a href="{{ route('adashi.show', $row->adashi->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:border-brand transition">
                    <div class="font-semibold truncate">{{ $row->adashi->name }}</div>
                    <div class="text-xs text-slate-400 mb-2">{{ $row->paid }}/{{ $row->members }} paid this cycle</div>
                    <div class="flex justify-between text-xs text-slate-500 mb-1"><span>Cycle collected</span><span>{{ $row->percent }}%</span></div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-brand" style="width: {{ $row->percent }}%"></div></div>
                    <div class="text-xs text-slate-400 mt-1">₦{{ number_format($row->collected) }} of ₦{{ number_format($row->cycle_target) }}</div>
                    @if ($row->pending)
                        <div class="text-xs text-amber-600 font-semibold mt-2">🔔 {{ $row->pending }} contribution(s) awaiting your verification</div>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
@endsection
