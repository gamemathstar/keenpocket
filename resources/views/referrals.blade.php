@extends('layouts.app')
@section('title', 'Referrals')
@section('heading', 'Invite & earn')

@section('content')
    <div class="bg-gradient-to-br from-sky-400 to-blue-600 text-white rounded-2xl p-5 mb-6 flex items-center gap-4 overflow-hidden">
        <div class="flex-1">
            <h2 class="text-xl font-extrabold">Bring your circle along 🎁</h2>
            <p class="text-sm text-white/90 mt-1">Saving is better together — invite friends and family to join you on KeenPocket.</p>
        </div>
        <img src="{{ asset('ant-k/kandfriendsceleb.png') }}" alt="K and friends" class="hidden sm:block h-28 -mb-5 -mr-2 drop-shadow">
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-1">Your invite link</h3>
            <p class="text-slate-500 text-sm mb-4">Share it — when a friend joins their first pocket or adashi, your referral counts.</p>

            <div class="flex items-center gap-2 mb-3">
                <input id="link" value="{{ $inviteLink }}" readonly class="flex-1 rounded-lg border border-slate-300 px-3 py-2 bg-slate-50 text-sm">
                <button onclick="navigator.clipboard.writeText(document.getElementById('link').value)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Copy</button>
            </div>

            <a href="{{ $whatsappUrl }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-medium px-4 py-2.5">
                <span>💬</span> Share on WhatsApp
            </a>

            <div class="mt-5 text-sm">
                Your code: <span class="font-mono font-semibold tracking-wider bg-brand-light text-brand-dark px-2 py-1 rounded">{{ $code }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-3">Your impact</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Invited</span><span class="font-semibold">{{ $stats['invited'] }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Qualified</span><span class="font-semibold">{{ $stats['qualified'] }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Rewarded</span><span class="font-semibold">{{ $stats['rewarded'] }}</span></div>
            </div>
        </div>
    </div>

    <h3 class="font-semibold mt-8 mb-3">People you invited</h3>
    <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 max-w-2xl">
        @forelse ($invitees as $i)
            <div class="px-4 py-3 flex items-center justify-between text-sm">
                <span>{{ $i->name ?? 'New user' }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ in_array($i->status, ['qualified','rewarded']) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $i->status }}</span>
            </div>
        @empty
            <div class="px-4 py-6 text-sm text-slate-500 text-center">No invites yet — share your link above.</div>
        @endforelse
    </div>
@endsection
