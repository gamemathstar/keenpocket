@extends('layouts.app')
@section('title', 'Super Admin')
@section('heading', 'Super Admin')

@section('content')
    <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 mb-6">
        <h3 class="font-semibold mb-1">🏫 School module</h3>
        <p class="text-sm text-slate-500">Status: <span class="font-medium text-slate-700">{{ config('school.enabled', true) ? 'Enabled' : 'Disabled' }}</span> ·
            {{ config('school.paid') ? 'Paid service (grant access after settling offline)' : 'Free' }}.</p>
    </div>

    <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 mb-6">
        <h3 class="font-semibold mb-1">🪙 Keens — creation costs</h3>
        <p class="text-sm text-slate-500 mb-4">Charge Keens to create or clone groups, in <strong>tiers by participant count</strong>. Set a base cost (covers the first tier), the tier size, and how much each extra tier adds. Super admins create free.</p>
        <form method="POST" action="{{ route('super-admin.coins') }}" class="space-y-4">
            @csrf
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="coins_enabled" value="1" {{ $coinCfg['enabled'] ? 'checked' : '' }} class="rounded border-slate-300 text-brand focus:ring-brand">
                <span class="font-medium">Charge Keens for creating pockets / adashis / schools</span>
            </label>

            @php $labels = ['pocket' => '👛 Pocket', 'adashi' => '🔄 Adashi', 'school' => '🏫 School']; @endphp
            <div class="grid md:grid-cols-3 gap-4">
                @foreach ($coinCfg['types'] as $type => $cfg)
                    <div class="rounded-2xl border-2 border-slate-100 p-4">
                        <div class="font-bold text-sm mb-2">{{ $labels[$type] }} <span class="text-xs text-slate-400 font-normal">· by {{ $cfg['unit'] }}</span></div>
                        <div class="space-y-2">
                            <div>
                                <label class="block text-xs font-medium mb-1">Base cost (Keens)</label>
                                <input type="number" name="cost_{{ $type }}" value="{{ $cfg['base'] }}" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-medium mb-1">Tier size</label>
                                    <input type="number" name="{{ $type }}_tier" value="{{ $cfg['tier'] }}" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">+ per tier</label>
                                    <input type="number" name="{{ $type }}_step" value="{{ $cfg['step'] }}" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-brand focus:ring-brand">
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-slate-500 space-y-0.5">
                            <div class="font-bold text-slate-400 uppercase tracking-wide text-[10px]">Tiers</div>
                            @foreach ($cfg['preview'] as $row)
                                <div class="flex justify-between"><span>≤ {{ $row['upTo'] }} {{ $cfg['unit'] }}</span><span class="font-bold text-brand-dark">{{ $row['cost'] }} 🪙</span></div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Save coin settings</button>
        </form>

        <form method="POST" action="{{ route('super-admin.keens') }}" class="flex flex-wrap items-end gap-2 border-t border-slate-100 pt-4 mt-4">
            @csrf
            <div class="flex-1 min-w-[12rem]">
                <label class="block text-xs font-medium mb-1">Top up a user (phone / email / username)</label>
                <input name="contact" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="08012345678">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Keens</label>
                <input type="number" name="amount" min="1" value="50" class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
            </div>
            <button class="btn-soft text-sm">🪙 Grant</button>
        </form>
    </div>

    <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 mb-6">
        <h3 class="font-semibold mb-3">Schools ({{ $schools->count() }})</h3>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($schools as $s)
                <li class="py-2 flex justify-between"><a href="{{ route('school.show', $s->id) }}" class="font-medium hover:underline">{{ $s->name }}</a><span class="text-slate-400">by {{ $s->owner }}</span></li>
            @empty
                <li class="py-2 text-slate-500">No schools yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
        <h3 class="font-semibold mb-1">Users — Keens &amp; school access</h3>
        <p class="text-sm text-slate-500 mb-3">Search a user to see their Keens balance, top up their wallet, or grant school-creation access.</p>
        <form method="GET" class="flex gap-2 mb-4 max-w-md">
            <input name="q" value="{{ $q }}" placeholder="Search by name, email or phone…" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
            <button class="rounded-lg bg-brand text-white px-4 text-sm">Search</button>
        </form>
        <ul class="divide-y divide-slate-100">
            @forelse ($users as $u)
                <li class="py-3 flex flex-wrap items-center justify-between gap-3 text-sm">
                    <div class="min-w-[12rem]">
                        <span class="font-medium">{{ $u->name }}</span>
                        @if ($u->isSuperAdmin())<span class="text-[10px] px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 font-bold uppercase ml-1">super</span>@endif
                        <span class="block text-xs text-slate-400">{{ $u->email }} · {{ $u->phone_number }}</span>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        {{-- Keens balance + inline top-up --}}
                        <span class="text-xs font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700" title="Keens balance">🪙 {{ number_format((int) $u->keens) }}</span>
                        <form method="POST" action="{{ route('super-admin.keens') }}" class="flex items-center gap-1.5">
                            @csrf
                            <input type="hidden" name="contact" value="{{ $u->email }}">
                            <input type="number" name="amount" min="1" value="50" class="w-20 rounded-lg border border-slate-300 px-2 py-1 text-sm focus:border-brand focus:ring-brand" aria-label="Keens to grant">
                            <button class="btn-soft text-xs">🪙 Grant</button>
                        </form>
                        {{-- School access --}}
                        @if ($u->canCreateSchool())
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">can create school</span>
                            @unless ($u->isSuperAdmin())
                                <form method="POST" action="{{ route('super-admin.revoke', $u->id) }}">@csrf<button class="text-xs text-red-500 hover:underline">revoke</button></form>
                            @endunless
                        @else
                            <form method="POST" action="{{ route('super-admin.grant', $u->id) }}">@csrf<button class="btn-soft text-xs">Grant school</button></form>
                        @endif
                    </div>
                </li>
            @empty
                <li class="py-2 text-slate-500 text-sm">No users found.</li>
            @endforelse
        </ul>
    </div>
@endsection
