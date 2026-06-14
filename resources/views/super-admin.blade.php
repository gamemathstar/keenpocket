@extends('layouts.app')
@section('title', 'Super Admin')
@section('heading', 'Super Admin')

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
        <h3 class="font-semibold mb-1">🏫 School module</h3>
        <p class="text-sm text-slate-500">Status: <span class="font-medium text-slate-700">{{ config('school.enabled', true) ? 'Enabled' : 'Disabled' }}</span> ·
            {{ config('school.paid') ? 'Paid service (grant access after settling offline)' : 'Free' }}.</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
        <h3 class="font-semibold mb-1">🪙 Keens — creation costs</h3>
        <p class="text-sm text-slate-500 mb-3">Charge Keens to create or clone groups. Costs scale by capacity (per 50 hands/members for pockets &amp; adashis, per 100 students for schools). Super admins create free.</p>
        <form method="POST" action="{{ route('super-admin.coins') }}" class="space-y-3">
            @csrf
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="coins_enabled" value="1" {{ $coinCfg['enabled'] ? 'checked' : '' }} class="rounded border-slate-300 text-brand focus:ring-brand">
                <span class="font-medium">Charge Keens for creating pockets / adashis / schools</span>
            </label>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="block text-xs font-medium mb-1">Pocket (per 50 hands)</label><input type="number" name="cost_pocket" value="{{ $coinCfg['pocket'] }}" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand"></div>
                <div><label class="block text-xs font-medium mb-1">Adashi (per 50 members)</label><input type="number" name="cost_adashi" value="{{ $coinCfg['adashi'] }}" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand"></div>
                <div><label class="block text-xs font-medium mb-1">School (per 100 students)</label><input type="number" name="cost_school" value="{{ $coinCfg['school'] }}" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand"></div>
            </div>
            <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Save coin settings</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
        <h3 class="font-semibold mb-3">Schools ({{ $schools->count() }})</h3>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($schools as $s)
                <li class="py-2 flex justify-between"><a href="{{ route('school.show', $s->id) }}" class="font-medium hover:underline">{{ $s->name }}</a><span class="text-slate-400">by {{ $s->owner }}</span></li>
            @empty
                <li class="py-2 text-slate-500">No schools yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold mb-3">Grant school-creation access</h3>
        <form method="GET" class="flex gap-2 mb-4 max-w-md">
            <input name="q" value="{{ $q }}" placeholder="Search by name, email or phone…" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
            <button class="rounded-lg bg-brand text-white px-4 text-sm">Search</button>
        </form>
        <ul class="divide-y divide-slate-100">
            @forelse ($users as $u)
                <li class="py-2.5 flex items-center justify-between gap-3 text-sm">
                    <div>
                        <span class="font-medium">{{ $u->name }}</span>
                        <span class="block text-xs text-slate-400">{{ $u->email }} · {{ $u->phone_number }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($u->canCreateSchool())
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">can create</span>
                            @unless ($u->isSuperAdmin())
                                <form method="POST" action="{{ route('super-admin.revoke', $u->id) }}">@csrf<button class="text-xs text-red-500 hover:underline">revoke</button></form>
                            @endunless
                        @else
                            <form method="POST" action="{{ route('super-admin.grant', $u->id) }}">@csrf<button class="btn-soft text-xs">Grant</button></form>
                        @endif
                    </div>
                </li>
            @empty
                <li class="py-2 text-slate-500 text-sm">No users found.</li>
            @endforelse
        </ul>
    </div>
@endsection
