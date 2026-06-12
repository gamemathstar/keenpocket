@extends('layouts.app')
@section('title', 'Manage Members')
@section('heading', 'Manage members')

@section('content')
    <div class="flex items-center justify-between">
        <a href="{{ route('adashi.show', $adashi->id) }}" class="text-sm text-brand-dark hover:underline">← Back to {{ $adashi->name }}</a>
        <a href="{{ route('adashi.records.export', $adashi->id) }}" class="text-sm rounded-lg border border-slate-300 hover:bg-slate-50 px-3 py-1.5">⬇ Export records (CSV)</a>
    </div>

    {{-- Admin controls for the current cycle --}}
    @if ($currentRecord)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mt-4">
            <h3 class="font-semibold mb-3">Cycle {{ $currentRecord->cycle_number }} controls
                <span class="text-xs font-normal text-slate-400">· {{ ucfirst(strtolower($currentRecord->status)) }}</span>
            </h3>
            <div class="flex flex-wrap items-end gap-3">
                {{-- Set receiver --}}
                <form method="POST" action="{{ route('adashi.admin', $adashi->id) }}" class="flex items-end gap-2">
                    @csrf
                    <input type="hidden" name="action" value="SET_RECEIVER">
                    <input type="hidden" name="record_id" value="{{ $currentRecord->id }}">
                    <div>
                        <label class="block text-xs font-medium mb-1">Set receiver</label>
                        <select name="receiver_user_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                            @foreach ($members->where('is_active', 1) as $m)
                                <option value="{{ $m->user_id }}" @selected($m->user_id == $currentRecord->receiver_user_id)>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="rounded-lg border border-slate-300 hover:bg-slate-50 px-3 py-2 text-sm">Set</button>
                </form>

                <form method="POST" action="{{ route('adashi.admin', $adashi->id) }}">
                    @csrf
                    <input type="hidden" name="action" value="MARK_PAID_OUT">
                    <input type="hidden" name="record_id" value="{{ $currentRecord->id }}">
                    <button class="rounded-lg bg-brand hover:bg-brand-dark text-white px-3 py-2 text-sm">Mark paid out</button>
                </form>

                <form method="POST" action="{{ route('adashi.admin', $adashi->id) }}">
                    @csrf
                    <input type="hidden" name="action" value="MARK_DISPUTE">
                    <input type="hidden" name="record_id" value="{{ $currentRecord->id }}">
                    <button class="rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50 px-3 py-2 text-sm">Flag dispute</button>
                </form>
            </div>
        </div>
    @endif

    {{-- Adashi status control --}}
    <div class="bg-white border border-slate-200 rounded-xl p-5 mt-4 flex items-center justify-between">
        <div>
            <span class="font-semibold">Status:</span>
            <span class="text-xs px-2 py-0.5 rounded-full ml-1 font-bold uppercase
                {{ $adashi->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-700' : ($adashi->status === 'PAUSED' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500') }}">
                {{ strtolower($adashi->status) }}
            </span>
        </div>
        @if ($adashi->status === 'ACTIVE')
            <form method="POST" action="{{ route('adashi.admin', $adashi->id) }}">
                @csrf<input type="hidden" name="action" value="PAUSE">
                <button class="rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50 px-4 py-2 text-sm font-bold">⏸ Pause adashi</button>
            </form>
        @elseif ($adashi->status === 'PAUSED')
            <form method="POST" action="{{ route('adashi.admin', $adashi->id) }}">
                @csrf<input type="hidden" name="action" value="RESUME">
                <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm font-bold">▶ Resume adashi</button>
            </form>
        @endif
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mt-6">
        {{-- Add member --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-1">Add a member</h3>
            <p class="text-sm text-slate-500 mb-4">Add by phone number. If they're not on KeenPocket yet, an account is created for them to claim later.</p>
            <form method="POST" action="{{ route('adashi.members.store', $adashi->id) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Phone number</label>
                    <input name="phone_number" value="{{ old('phone_number') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="08012345678">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Name <span class="text-slate-400 font-normal">(if new)</span></label>
                    <input name="name" value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="Member name">
                </div>
                <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Add member</button>
            </form>
        </div>

        {{-- Current members --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-3">Members ({{ $members->count() }})</h3>
            <ul class="divide-y divide-slate-100">
                @foreach ($members as $m)
                    <li class="py-3 flex items-center justify-between text-sm">
                        <span class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-xs">#{{ $m->position }}</span>
                            <span>
                                <span class="font-medium">{{ $m->name }}</span>
                                <span class="block text-xs text-slate-400">{{ $m->phone_number }}</span>
                            </span>
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $m->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $m->is_active ? 'active' : 'inactive' }}</span>
                            @if ($m->user_id != $adashi->admin_id)
                                <form method="POST" action="{{ route('adashi.admin', $adashi->id) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="{{ $m->is_active ? 'DEACTIVATE_MEMBER' : 'REACTIVATE_MEMBER' }}">
                                    <input type="hidden" name="member_user_id" value="{{ $m->user_id }}">
                                    <button class="text-xs text-slate-400 hover:text-slate-700 underline">{{ $m->is_active ? 'deactivate' : 'reactivate' }}</button>
                                </form>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
