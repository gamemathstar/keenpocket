@extends('layouts.app')
@section('title', 'Review contribution')
@section('heading', 'Review contribution')

@section('content')
    <a href="{{ route('invoices.create', $pocket->id) }}" class="inline-flex items-center text-sm text-brand-dark hover:underline mb-4">← Edit amount</a>
    <div class="max-w-lg bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
        <h2 class="text-lg font-semibold mb-1">How your ₦{{ number_format($total) }} is split</h2>
        <p class="text-sm text-slate-500 mb-4">We filled the months you owe ({{ $pocket->title }}). Adjust any amount before saving.</p>

        <form method="POST" action="{{ route('invoices.store', $pocket->id) }}" class="space-y-3" id="kpForm">
            @csrf
            <input type="hidden" name="balance" value="{{ $total }}">

            @if ($project)
                <div class="flex items-center justify-between gap-3 rounded-lg bg-brand-light/60 px-3 py-2">
                    <label class="text-sm font-medium">🤲 Donation <span class="text-slate-400 font-normal">— {{ $project->title }}</span></label>
                    <div class="flex items-center gap-1">
                        <span class="text-slate-400">₦</span>
                        <input type="number" name="donation" value="{{ $donation }}" min="0" class="kp-amt w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-sm text-right focus:border-brand focus:ring-brand">
                    </div>
                </div>
            @endif

            @forelse ($plan as $i => $row)
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2">
                    <div>
                        <span class="font-medium">{{ $row['label'] }}</span>
                        <span class="block text-xs text-slate-400">
                            month {{ $row['month'] }}
                            @if ($row['owed'] < $row['monthly']) · ₦{{ number_format($row['owed']) }} still owed @endif
                            @if ($row['amount'] < $row['monthly'] && $row['amount'] < $row['owed']) · part payment @endif
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-slate-400">₦</span>
                        <input type="hidden" name="months[{{ $i }}]" value="{{ $row['month'] }}">
                        <input type="number" name="amounts[{{ $i }}]" value="{{ $row['amount'] }}" min="0" class="kp-amt w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-sm text-right focus:border-brand focus:ring-brand">
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">All your months are already fully paid 🎉 — nothing to allocate. You can still add a donation above.</p>
            @endforelse

            <div class="flex items-center justify-between pt-2 font-semibold">
                <span>Allocated</span>
                <span><span id="kpTotal">{{ number_format($total) }}</span> / ₦{{ number_format($total) }}</span>
            </div>
            <p id="kpWarn" class="hidden text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                ⚠️ You've allocated more than your ₦{{ number_format($total) }} balance. Reduce an amount to continue.
            </p>

            <div class="flex gap-3 pt-2">
                <button id="kpSubmit" class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5 disabled:opacity-50 disabled:cursor-not-allowed">Confirm &amp; save</button>
                <a href="{{ route('pockets.show', $pocket->id) }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            var form = document.getElementById('kpForm');
            var balance = {{ (int) $total }};
            var totalEl = document.getElementById('kpTotal');
            var warn = document.getElementById('kpWarn');
            var submit = document.getElementById('kpSubmit');
            function recompute() {
                var t = 0;
                form.querySelectorAll('.kp-amt').forEach(function (el) { t += parseInt(el.value || '0', 10) || 0; });
                totalEl.textContent = '₦' + t.toLocaleString();
                var over = t > balance;
                warn.classList.toggle('hidden', !over);
                totalEl.classList.toggle('text-red-600', over);
                submit.disabled = over;
            }
            form.addEventListener('input', recompute);
            recompute();
        })();
    </script>
@endsection
