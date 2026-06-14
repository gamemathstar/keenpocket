{{-- Limited public view shown to people who are NOT members of this pocket. --}}
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 text-center">
        <h2 class="text-2xl font-extrabold">{{ $pocket->title }}</h2>
        @if ($pocket->description)<p class="text-sm text-slate-500 mt-1">{{ $pocket->description }}</p>@endif

        <div class="grid grid-cols-3 gap-3 mt-5 text-center">
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">Per hand</div><div class="font-bold">₦{{ number_format($pocket->amount_per_hand) }}</div></div>
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">Hands left</div><div class="font-bold">{{ is_null($handsLeft) ? '∞' : ($handsLeft ?: 'Full') }}</div></div>
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">Duration</div><div class="font-bold">{{ $pocket->month_count }} mo</div></div>
        </div>

        {{-- Admin trust spotlight --}}
        @if ($owner)
            <div class="flex items-center gap-3 mt-5 rounded-xl border border-slate-100 p-3 text-left">
                <x-avatar :user="$owner" :size="52" />
                <div class="min-w-0">
                    <div class="font-semibold truncate">{{ $owner->name }} <span class="text-xs font-normal text-slate-400">· admin</span></div>
                    <div class="text-xs text-slate-500">
                        <span class="px-1.5 py-0.5 rounded-full bg-brand-light text-brand-dark font-semibold">{{ $reputation['band'] }} · {{ $reputation['score'] }}</span>
                        @if ($adminRating['count'])
                            · ⭐ {{ number_format($adminRating['average'], 1) }} ({{ $adminRating['count'] }})
                        @else
                            · no ratings yet
                        @endif
                    </div>
                </div>
                <a href="{{ route('users.show', $owner->id) }}" class="ml-auto text-xs text-brand-dark hover:underline shrink-0">profile →</a>
            </div>
        @endif

        {{-- Charity goal (read-only teaser) --}}
        @if ($charity)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-left">
                <div class="text-sm font-semibold text-amber-800">🤲 {{ $charity['project']->title }}</div>
                @if ($charity['goal_type'] === 'amount' && $charity['target_amount'] > 0)
                    <div class="text-xs text-amber-700 mt-0.5">Goal: ₦{{ number_format($charity['target_amount']) }} · raised ₦{{ number_format($charity['raised']) }}</div>
                @endif
                <div class="text-xs text-amber-600 mt-0.5">Join to contribute Sadaqah with the group.</div>
            </div>
        @endif

        {{-- Join / request --}}
        <div class="mt-6">
            @if ($hasPending)
                <div class="text-sm text-amber-700 bg-amber-50 rounded-lg px-3 py-2">⏳ Your join request is pending the admin's approval.</div>
            @elseif (!$pocket->status)
                <div class="text-sm text-slate-500">🔒 This pocket is invitation-only. Ask the admin to invite you.</div>
            @else
                <button type="button" onclick="document.getElementById('joinModal').classList.remove('hidden')"
                        class="rounded-lg bg-brand hover:bg-brand-dark text-white font-bold px-6 py-3">Request to join</button>
            @endif
        </div>
    </div>
    <p class="text-center text-xs text-slate-400 mt-3">Member details are private until you join.</p>
</div>

{{-- Join modal: T&C + pocket rules + guarantor --}}
@if ($pocket->status && !$hasPending)
    <div id="joinModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
         onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
                <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                <span class="font-semibold">Join {{ $pocket->title }}</span>
                <button type="button" onclick="document.getElementById('joinModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" action="{{ route('pockets.join', $pocket->id) }}" class="p-5 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium mb-1">How many hands?</label>
                    <input type="number" name="hand_count" value="1" min="1" class="w-28 rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                @if ($pocket->guarantor_required)
                    <div>
                        <label class="block text-xs font-medium mb-1">Guarantor <span class="text-slate-400 font-normal">(phone/email of someone who'll vouch for you)</span></label>
                        <input name="guarantor_contact" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="guarantor@example.com">
                        <p class="text-xs text-slate-400 mt-1">They must recommend you before the admin can accept.</p>
                    </div>
                @endif
                @if ($pocket->rules)
                    <div class="rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600 px-3 py-2 whitespace-pre-line"><span class="font-semibold">Group rules:</span> {{ $pocket->rules }}</div>
                @endif
                <x-terms-notice variant="join" />
                <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Submit request</button>
            </form>
        </div>
    </div>
@endif
