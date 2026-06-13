@props(['type', 'id', 'disputes', 'isAdmin' => false, 'canRaise' => false])

<div class="bg-white rounded-xl border border-slate-200 p-5 mt-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">⚖️ Disputes</h3>
        @if ($canRaise)
            <button type="button" onclick="document.getElementById('disputeModal').classList.remove('hidden')" class="btn-soft text-sm">Raise a dispute</button>
        @endif
    </div>

    <ul class="divide-y divide-slate-100">
        @forelse ($disputes as $d)
            @php
                $badge = $d->status === 'OPEN' ? 'bg-amber-100 text-amber-700'
                    : ($d->status === 'RESOLVED' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500');
            @endphp
            <li class="py-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-sm">{{ $d->subject }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $badge }}">{{ ucfirst(strtolower($d->status)) }}</span>
                </div>
                <p class="text-sm text-slate-600 mt-1 whitespace-pre-line">{{ $d->body }}</p>
                <p class="text-xs text-slate-400 mt-1">@if ($isAdmin && isset($d->raiser_name))by {{ $d->raiser_name }} · @endif{{ \Illuminate\Support\Carbon::parse($d->created_at)->diffForHumans() }}</p>
                @if ($d->status !== 'OPEN' && $d->resolution)
                    <p class="text-xs text-slate-500 mt-1 rounded-lg bg-slate-50 px-2 py-1.5">Admin: {{ $d->resolution }}</p>
                @endif
                @if ($isAdmin && $d->status === 'OPEN')
                    <form method="POST" action="{{ route('disputes.resolve', $d->id) }}" class="mt-2 flex flex-wrap items-center gap-2">
                        @csrf
                        <input name="resolution" maxlength="2000" class="flex-1 min-w-[12rem] rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-brand focus:ring-brand" placeholder="Resolution note (optional)">
                        <button class="btn-soft text-sm">Resolve</button>
                        <button formaction="{{ route('disputes.dismiss', $d->id) }}" class="btn-soft text-sm">Dismiss</button>
                    </form>
                @endif
            </li>
        @empty
            <li class="py-3 text-sm text-slate-500">No disputes. 🤝</li>
        @endforelse
    </ul>
</div>

@if ($canRaise)
    <div id="disputeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
         onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
                <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                <span class="font-semibold">Raise a dispute</span>
                <button type="button" onclick="document.getElementById('disputeModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" action="{{ route('disputes.raise', [$type, $id]) }}" class="p-5 space-y-3">
                @csrf
                <input name="subject" required maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Subject (e.g. missed payout)">
                <textarea name="body" required maxlength="2000" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Describe the issue for the admin…"></textarea>
                <p class="text-xs text-slate-400">KeenPocket keeps the record; resolution is between you and your group's admin.</p>
                <button class="w-full rounded-lg bg-brand text-white font-medium py-2.5">Submit dispute</button>
            </form>
        </div>
    </div>
@endif
