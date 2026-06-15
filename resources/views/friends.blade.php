@extends('layouts.app')
@section('title', 'Friends & Invites')
@section('heading', 'Friends & Invites')

@section('content')
    {{-- Hero --}}
    <section class="bg-brand-light rounded-[2rem] border-b-8 border-brand p-6 sm:p-7 mb-8 flex items-center justify-between gap-4 max-w-3xl">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-dark leading-tight">Friends &amp; invites 👥</h2>
            <p class="text-slate-600 mt-1">Connect with people you trust — and invite new ones to KeenPocket.</p>
        </div>
        <x-mascot :size="84" class="hidden sm:block drop-shadow-xl" />
    </section>

    <div class="max-w-3xl space-y-6">
        {{-- Add a friend --}}
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
            <div class="flex items-center justify-between gap-2 mb-1">
                <h3 class="font-extrabold">Add a friend</h3>
                <div class="flex gap-2">
                    <button type="button" onclick="kpToggleScan()" class="text-xs rounded-full border-2 border-slate-200 hover:border-brand font-bold text-slate-600 px-3 py-1.5">📷 Scan QR</button>
                    <button type="button" onclick="document.getElementById('myQrBox').classList.toggle('hidden')" class="text-xs rounded-full border-2 border-slate-200 hover:border-brand font-bold text-slate-600 px-3 py-1.5">🔳 My QR</button>
                </div>
            </div>
            <p class="text-sm text-slate-500 mb-3">Search by phone, email or username — or scan a friend's QR. They accept before you're connected.</p>
            <form id="addFriendForm" method="POST" action="{{ route('friends.store') }}" class="flex gap-2">
                @csrf
                <input id="friendContact" name="contact" value="{{ old('contact', request('add')) }}" required class="flex-1 rounded-xl border-2 border-slate-200 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" placeholder="08012345678 · friend@example.com · username">
                <button class="rounded-xl bg-brand hover:bg-brand-dark text-white font-bold px-5">Send request</button>
            </form>

            {{-- QR scanner --}}
            <div id="scanBox" class="hidden mt-4">
                <div id="qrReader" class="mx-auto max-w-xs rounded-2xl overflow-hidden border-2 border-slate-200"></div>
                <p class="text-xs text-slate-400 mt-2 text-center">Point your camera at a friend's KeenPocket QR. (Camera needs HTTPS or localhost.)</p>
            </div>

            {{-- My QR code --}}
            <div id="myQrBox" class="hidden mt-4 text-center">
                <div id="myQr" class="inline-block p-3 bg-white rounded-2xl border-2 border-slate-200"
                     data-qr="{{ url('/friends').'?add='.urlencode(auth()->user()->username ?: auth()->user()->phone_number) }}"></div>
                <p class="text-xs text-slate-500 mt-2">Have a friend scan this to add <span class="font-bold">{{ auth()->user()->username ?: auth()->user()->phone_number }}</span>.</p>
            </div>
        </div>

        {{-- Incoming requests --}}
        @if ($incoming->count())
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                <h3 class="font-extrabold mb-3">Requests for you <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $incoming->count() }}</span></h3>
                <ul class="divide-y divide-slate-100">
                    @foreach ($incoming as $req)
                        <li class="py-3 flex items-center gap-3">
                            <x-avatar :user="optional($req->requester)->name ?? 'U'" :size="40" />
                            <div class="min-w-0 flex-1">
                                <div class="font-bold truncate">{{ optional($req->requester)->name }}</div>
                                <div class="text-xs text-slate-400">wants to be your friend</div>
                            </div>
                            <form method="POST" action="{{ route('friends.accept', $req->id) }}">@csrf<button class="text-xs rounded-md bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5">Accept</button></form>
                            <form method="POST" action="{{ route('friends.decline', $req->id) }}">@csrf<button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-3 py-1.5">Decline</button></form>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- My friends --}}
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
            <h3 class="font-extrabold mb-3">My friends <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-brand-light text-brand-dark">{{ $friends->count() }}</span></h3>
            @forelse ($friends as $friend)
                <div class="flex items-center gap-3 py-2 border-b border-slate-100 last:border-0">
                    <x-avatar :user="$friend->name" :size="40" />
                    <a href="{{ route('users.show', $friend->id) }}" class="min-w-0 flex-1 font-bold truncate hover:underline">{{ $friend->name }}</a>
                    <form method="POST" action="{{ route('friends.remove', $friend->id) }}" onsubmit="return confirm('Remove {{ $friend->name }} from your friends?')">
                        @csrf
                        <button class="text-xs text-red-500 hover:underline">Remove</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-slate-500">No friends yet — send a request above to get started.</p>
            @endforelse
        </div>

        {{-- Sent requests --}}
        @if ($outgoing->count())
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                <h3 class="font-extrabold mb-3">Sent requests</h3>
                <ul class="divide-y divide-slate-100">
                    @foreach ($outgoing as $req)
                        <li class="py-3 flex items-center gap-3">
                            <x-avatar :user="optional($req->recipient)->name ?? 'U'" :size="40" />
                            <div class="min-w-0 flex-1">
                                <div class="font-bold truncate">{{ optional($req->recipient)->name }}</div>
                                <div class="text-xs text-slate-400">pending — waiting for them to accept</div>
                            </div>
                            <form method="POST" action="{{ route('friends.cancel', $req->id) }}">@csrf<button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-3 py-1.5">Cancel</button></form>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Invite to KeenPocket (referrals) --}}
        <div class="rounded-[1.5rem] card-depth border-2 border-brand/20 bg-gradient-to-br from-sky-400 to-blue-600 text-white p-5 flex items-center gap-4 overflow-hidden">
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-extrabold">Not on KeenPocket yet? Invite them 🎁</h3>
                <p class="text-sm text-white/90 mt-1">Share your link — when someone joins and starts saving, your referral counts.</p>
            </div>
            <img src="{{ asset('ant-k/kandfriendsceleb.png') }}" alt="" class="hidden sm:block h-24 -mb-5 drop-shadow">
        </div>

        <div class="grid sm:grid-cols-3 gap-6">
            <div class="sm:col-span-2 bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                <h3 class="font-extrabold mb-1">Your invite link</h3>
                <p class="text-slate-500 text-sm mb-3">Share it with people who don't have an account yet.</p>
                <div class="flex items-center gap-2 mb-3">
                    <input id="inviteLink" value="{{ $referral['inviteLink'] }}" readonly class="flex-1 rounded-xl border-2 border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('inviteLink').value)" class="rounded-xl border-2 border-slate-200 hover:border-brand px-3 py-2 text-sm font-bold">Copy</button>
                </div>
                <a href="{{ $referral['whatsappUrl'] }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2.5">
                    <span>💬</span> Share on WhatsApp
                </a>
                <div class="mt-4 text-sm">Your code: <span class="font-mono font-bold tracking-wider bg-brand-light text-brand-dark px-2 py-1 rounded">{{ $referral['code'] }}</span></div>
            </div>
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                <h3 class="font-extrabold mb-3">Your impact</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Invited</span><span class="font-extrabold">{{ $referral['stats']['invited'] }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Qualified</span><span class="font-extrabold">{{ $referral['stats']['qualified'] }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Rewarded</span><span class="font-extrabold">{{ $referral['stats']['rewarded'] }}</span></div>
                </div>
            </div>
        </div>

        @if ($referral['invitees']->count())
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                <h3 class="font-extrabold mb-3">People you invited</h3>
                <ul class="divide-y divide-slate-100">
                    @foreach ($referral['invitees'] as $i)
                        <li class="py-2.5 flex items-center justify-between text-sm">
                            <span>{{ $i->name ?? 'New user' }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ in_array($i->status, ['qualified','rewarded']) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $i->status }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        // Render my QR code.
        (function () {
            var box = document.getElementById('myQr');
            if (box && window.QRCode && !box.dataset.done) {
                new QRCode(box, { text: box.dataset.qr, width: 180, height: 180, colorDark: '#1899d6' });
                box.dataset.done = '1';
            }
        })();

        // QR scanner → fills the contact field and submits.
        var kpScanner = null;
        function kpStopScan() {
            if (kpScanner) { kpScanner.stop().then(function () { kpScanner.clear(); }).catch(function () {}); kpScanner = null; }
        }
        function kpToggleScan() {
            var sb = document.getElementById('scanBox');
            sb.classList.toggle('hidden');
            if (sb.classList.contains('hidden')) { kpStopScan(); return; }
            if (!window.Html5Qrcode) { alert('Scanner failed to load — check your connection.'); return; }
            kpScanner = new Html5Qrcode('qrReader');
            kpScanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, function (text) {
                var val = text;
                try { val = (new URL(text)).searchParams.get('add') || text; } catch (e) {}
                kpStopScan();
                var input = document.getElementById('friendContact');
                input.value = val;
                document.getElementById('addFriendForm').submit();
            }, function () {}).catch(function () {
                alert('Could not access the camera. Allow camera access (and use HTTPS).');
            });
        }
    </script>
@endsection
