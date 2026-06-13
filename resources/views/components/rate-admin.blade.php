@props(['action', 'average' => ['average' => null, 'count' => 0], 'my' => null])
@php $rid = 'rate_'.substr(md5($action), 0, 8); @endphp

<button type="button" onclick="document.getElementById('{{ $rid }}').classList.remove('hidden')"
        class="inline-flex items-center gap-1.5 rounded-lg border-2 border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 font-semibold px-4 py-2 text-sm">
    ⭐ Rate admin
    @if (($average['count'] ?? 0) > 0)
        <span class="text-amber-600">· {{ number_format($average['average'], 1) }} ({{ $average['count'] }})</span>
    @endif
</button>

<div id="{{ $rid }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
     onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        {{-- Branded header: app logo top-left --}}
        <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
            <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
            <span class="font-semibold">Rate the admin</span>
            <button type="button" onclick="document.getElementById('{{ $rid }}').classList.add('hidden')"
                    class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ $action }}" class="p-5 space-y-4">
            @csrf
            <div class="flex justify-center gap-2" data-stars="{{ $rid }}">
                @for ($s = 1; $s <= 5; $s++)
                    <button type="button" data-val="{{ $s }}" aria-label="{{ $s }} stars"
                            class="kp-star text-4xl leading-none text-slate-300 transition-transform hover:scale-110">★</button>
                @endfor
            </div>
            <input type="hidden" name="stars" value="{{ (int) ($my ?? 0) }}" id="{{ $rid }}_val">
            <div>
                <label class="block text-xs font-medium mb-1">Comment (optional)</label>
                <input name="comment" maxlength="500" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="How is the admin running this?">
            </div>
            <div class="flex gap-2">
                <button class="flex-1 rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Submit rating</button>
                <button type="button" onclick="document.getElementById('{{ $rid }}').classList.add('hidden')"
                        class="rounded-lg border border-slate-300 px-4 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var wrap = document.querySelector('[data-stars="{{ $rid }}"]');
        if (!wrap) return;
        var hidden = document.getElementById('{{ $rid }}_val');
        var stars = wrap.querySelectorAll('.kp-star');
        function paint(n) {
            stars.forEach(function (s, i) {
                s.classList.toggle('text-amber-400', i < n);
                s.classList.toggle('text-slate-300', i >= n);
            });
        }
        stars.forEach(function (s) {
            s.addEventListener('mouseover', function () { paint(parseInt(s.dataset.val, 10)); });
            s.addEventListener('click', function () { hidden.value = s.dataset.val; paint(parseInt(s.dataset.val, 10)); });
        });
        wrap.addEventListener('mouseleave', function () { paint(parseInt(hidden.value || '0', 10)); });
        paint(parseInt(hidden.value || '0', 10));
    })();
</script>
