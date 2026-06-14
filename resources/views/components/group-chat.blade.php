@props(['type', 'id', 'messages', 'canPost' => false])
@php $lastId = optional($messages->last())->id ?? 0; @endphp

{{-- Floating chat bubble (bottom-right) --}}
<button type="button" id="chatFab" onclick="kpToggleChat()" aria-label="Group chat"
        class="fixed z-40 right-4 bottom-20 md:right-6 md:bottom-6 h-14 w-14 rounded-full bg-brand text-white shadow-xl flex items-center justify-center text-2xl hover:brightness-105 active:translate-y-0.5 transition">
    💬
    <span id="chatBadge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-[11px] font-bold rounded-full h-5 min-w-[1.25rem] px-1 flex items-center justify-center shadow">0</span>
</button>

{{-- Messenger-style panel --}}
<div id="chatPanel" class="hidden fixed z-40 right-4 bottom-36 md:right-6 md:bottom-24 w-[22rem] max-w-[calc(100vw-2rem)] bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col" style="height: min(70vh, 32rem);">
    <div class="flex items-center gap-2 px-4 py-3 bg-brand text-white shrink-0">
        <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md ring-2 ring-white/40" alt="KeenPocket">
        <span class="font-semibold">Group chat</span>
        <span class="ml-1 h-2 w-2 rounded-full bg-emerald-300" title="Live"></span>
        <button type="button" onclick="kpToggleChat()" class="ml-auto text-white/80 hover:text-white text-2xl leading-none">&times;</button>
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-3 space-y-3 bg-slate-50" id="chatScroll">
        @forelse ($messages as $m)
            @php $mine = $m->user_id == auth()->id(); @endphp
            <div class="flex {{ $mine ? 'justify-end' : '' }}">
                <div class="max-w-[80%]">
                    <div class="text-[11px] text-slate-400 mb-0.5 {{ $mine ? 'text-right' : '' }}">{{ $mine ? 'You' : $m->name }} · {{ \Illuminate\Support\Carbon::parse($m->created_at)->diffForHumans(null, true) }} ago</div>
                    <div class="text-sm rounded-2xl px-3 py-2 {{ $mine ? 'bg-brand text-white rounded-br-sm' : 'bg-white border border-slate-200 text-slate-800 rounded-bl-sm' }}">{{ $m->body }}</div>
                </div>
            </div>
        @empty
            <p id="chatEmpty" class="text-sm text-slate-500 py-6 text-center">No messages yet — say hello 👋</p>
        @endforelse
    </div>

    @if ($canPost)
        <form id="chatForm" method="POST" action="{{ route('chat.post', [$type, $id]) }}" class="flex gap-2 p-3 border-t border-slate-100 shrink-0">
            @csrf
            <input name="body" required maxlength="1000" autocomplete="off"
                   class="flex-1 rounded-full border border-slate-300 px-4 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Message the group…">
            <button class="rounded-full bg-brand text-white h-10 w-10 flex items-center justify-center shrink-0" aria-label="Send">➤</button>
        </form>
    @else
        <p class="text-sm text-slate-500 p-3 border-t border-slate-100 shrink-0">Only members can post here.</p>
    @endif
</div>

<script>
(function () {
    var feedUrl = @json(route('chat.feed', [$type, $id]));
    var postUrl = @json(route('chat.post', [$type, $id]));
    var csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
    var lastId = {{ (int) $lastId }};
    var unread = 0;

    var panel = document.getElementById('chatPanel');
    var scroll = document.getElementById('chatScroll');
    var badge = document.getElementById('chatBadge');
    var form = document.getElementById('chatForm');

    function isOpen() { return !panel.classList.contains('hidden'); }
    function atBottom() { return scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 60; }
    function toBottom() { scroll.scrollTop = scroll.scrollHeight; }

    function beep() {
        try {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            var ctx = new Ctx(), o = ctx.createOscillator(), g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination); o.type = 'sine'; o.frequency.value = 680;
            g.gain.setValueAtTime(0.0001, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + 0.01);
            g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.32);
            o.start(); o.stop(ctx.currentTime + 0.33);
        } catch (e) {}
    }

    function setBadge(n) {
        unread = n;
        if (n > 0) { badge.textContent = n > 99 ? '99+' : n; badge.classList.remove('hidden'); }
        else { badge.classList.add('hidden'); }
    }

    function render(m) {
        var empty = document.getElementById('chatEmpty');
        if (empty) empty.remove();
        var wrap = document.createElement('div');
        wrap.className = 'flex' + (m.mine ? ' justify-end' : '');
        var col = document.createElement('div'); col.className = 'max-w-[80%]';
        var meta = document.createElement('div');
        meta.className = 'text-[11px] text-slate-400 mb-0.5' + (m.mine ? ' text-right' : '');
        meta.textContent = m.name + ' · ' + m.ago;
        var bub = document.createElement('div');
        bub.className = 'text-sm rounded-2xl px-3 py-2 ' + (m.mine ? 'bg-brand text-white rounded-br-sm' : 'bg-white border border-slate-200 text-slate-800 rounded-bl-sm');
        bub.textContent = m.body;
        col.appendChild(meta); col.appendChild(bub); wrap.appendChild(col);
        scroll.appendChild(wrap);
    }

    function poll() {
        fetch(feedUrl + '?after=' + lastId, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (list) {
                if (!Array.isArray(list) || !list.length) return;
                var stick = atBottom();
                var newFromOthers = 0;
                list.forEach(function (m) { render(m); lastId = Math.max(lastId, m.id); if (!m.mine) newFromOthers++; });
                if (isOpen()) { if (stick) toBottom(); }
                if (newFromOthers > 0) { beep(); if (!isOpen()) setBadge(unread + newFromOthers); }
            })
            .catch(function () {});
    }

    window.kpToggleChat = function () {
        panel.classList.toggle('hidden');
        if (isOpen()) { setBadge(0); toBottom(); var i = panel.querySelector('input[name=body]'); if (i) i.focus(); }
    };

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = form.querySelector('input[name=body]');
            var body = (input.value || '').trim();
            if (!body) return;
            input.value = '';
            fetch(postUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ body: body }),
            }).then(function (r) { return r.ok ? r.json() : null; })
              .then(function (m) { if (m) { render(m); lastId = Math.max(lastId, m.id); toBottom(); } })
              .catch(function () {});
        });
    }

    setInterval(poll, 5000);
})();
</script>
