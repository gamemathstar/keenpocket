@extends('layouts.app')
@section('title', 'Manage Pocket')
@section('heading', 'Manage pocket')

@section('content')
    <a href="{{ route('pockets.show', $pocket->id) }}" class="text-sm text-brand-dark hover:underline">← Back to {{ $pocket->title }}</a>

    <div class="flex items-center justify-between bg-white rounded-xl border border-slate-200 p-5 mt-4 max-w-5xl">
        <div>
            <div class="font-semibold">{{ $pocket->title }}</div>
            <div class="text-sm text-slate-500">{{ $pocket->status ? 'Open — anyone can join' : 'Closed — invitation only' }}</div>
        </div>
        <form method="POST" action="{{ route('pockets.toggleStatus', $pocket->id) }}">
            @csrf
            <button class="rounded-lg border border-slate-300 hover:bg-slate-50 px-4 py-2 text-sm">
                {{ $pocket->status ? 'Close pocket' : 'Open pocket' }}
            </button>
        </form>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mt-6 max-w-5xl">
        {{-- Add member --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-1">Add a member</h3>
            <p class="text-sm text-slate-500 mb-4">Add by phone. New numbers get a placeholder account to claim later.</p>
            <form method="POST" action="{{ route('pockets.addMember', $pocket->id) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Phone number</label>
                    <input name="phone_number" value="{{ old('phone_number') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="08012345678">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Name <span class="text-slate-400 font-normal">(if new)</span></label>
                    <input name="name" value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Hands</label>
                    <input type="number" name="hand_count" value="1" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Add member</button>
            </form>
        </div>

        {{-- Members --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-3">Members ({{ $members->where('status', 1)->count() }})</h3>
            <ul class="divide-y divide-slate-100">
                @foreach ($members as $m)
                    <li class="py-3 flex items-center justify-between text-sm">
                        <span>
                            <span class="font-medium">{{ $m->name }}</span>
                            <span class="block text-xs text-slate-400">{{ $m->phone_number }}</span>
                        </span>
                        <span class="flex items-center gap-3">
                            <span class="text-slate-500">{{ (int) $m->hand_count }} hand(s)</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $m->status ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $m->status ? 'active' : 'pending' }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
