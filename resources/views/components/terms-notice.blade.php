@props(['variant' => 'join', 'checkbox' => true])
@php
    $copy = [
        'join' => 'KeenPocket is a record-keeping tool — not a bank or financial service. We never receive, hold, or move any money. All contributions and payouts happen directly between you and the group, offline. Only join groups whose admin you personally know and trust. KeenPocket is not responsible for any contribution, payout, dispute, or loss.',
        'create' => 'You are creating a group you will run yourself. KeenPocket only keeps records — it never holds, receives, or moves money. You alone are responsible for collecting and disbursing funds directly with your members. Only admit people you personally know and trust.',
        'add' => 'Only add people you personally know and trust. KeenPocket keeps records only — it holds no money and is not responsible for any contribution, payout, or dispute.',
    ];
    $confirm = [
        'join' => 'I understand KeenPocket only keeps records and holds no money, and I personally know this group\'s admin.',
        'create' => 'I understand KeenPocket only keeps records and holds no money — I am responsible for my group\'s funds.',
        'add' => 'I personally know this member, and understand KeenPocket holds no money.',
    ];
@endphp
<div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs px-3 py-2.5 leading-relaxed">
    ⚠️ {{ $copy[$variant] ?? $copy['join'] }}
</div>
@if ($checkbox)
    <label class="flex items-start gap-2 text-sm text-slate-600 mt-2">
        <input type="checkbox" name="accept_terms" value="1" required class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
        <span>{{ $confirm[$variant] ?? $confirm['join'] }}</span>
    </label>
@endif
