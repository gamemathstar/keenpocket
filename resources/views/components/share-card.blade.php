@props(['cardTitle', 'cardBig', 'cardSub' => '', 'shareText', 'shareUrl'])
@php $waHref = 'https://wa.me/?text='.urlencode($shareText.' '.$shareUrl); @endphp

<button type="button" onclick="document.getElementById('shareModal').classList.remove('hidden')" class="btn-soft text-sm">📣 Share</button>

<div id="shareModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
     onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
            <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
            <span class="font-semibold">Share</span>
            <button type="button" onclick="document.getElementById('shareModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-5 space-y-4">
            {{-- Branded milestone card --}}
            <div class="rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 text-white p-6 text-center shadow-inner">
                <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-12 w-12 mx-auto rounded-xl ring-2 ring-white/50" alt="">
                <div class="text-sm opacity-90 mt-3">{{ $cardTitle }}</div>
                <div class="text-3xl font-extrabold mt-1 leading-tight">{{ $cardBig }}</div>
                @if ($cardSub)<div class="text-xs opacity-90 mt-1">{{ $cardSub }}</div>@endif
                <div class="text-[11px] font-bold uppercase tracking-widest opacity-80 mt-4">KeenPocket · save together</div>
            </div>

            <div class="flex gap-2">
                <a href="{{ $waHref }}" target="_blank" rel="noopener"
                   class="flex-1 text-center rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2.5">Share on WhatsApp</a>
                <button type="button" onclick="kpCopy('{{ addslashes($shareText.' '.$shareUrl) }}', this)" class="btn-soft">Copy link</button>
            </div>
            <p class="text-xs text-slate-400 text-center">Tip: screenshot the card above to post on your status.</p>
        </div>
    </div>
</div>
