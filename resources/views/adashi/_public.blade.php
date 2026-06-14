{{-- Limited public view shown to people who are NOT members of this adashi. --}}
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 text-center">
        <h2 class="text-2xl font-extrabold">{{ $adashi->name }}</h2>
        <p class="text-sm text-slate-500 mt-1">Rotating savings · {{ ucfirst(strtolower($adashi->rotation_mode)) }} rotation</p>

        <div class="grid grid-cols-3 gap-3 mt-5 text-center">
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">Per cycle</div><div class="font-bold">₦{{ number_format($adashi->amount_per_cycle) }}</div></div>
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">Members</div><div class="font-bold">{{ $adashi->total_members }}</div></div>
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">Every</div><div class="font-bold">{{ $adashi->cycle_duration_days }}d</div></div>
        </div>

        @if ($admin)
            <div class="flex items-center gap-3 mt-5 rounded-xl border border-slate-100 p-3 text-left">
                <x-avatar :user="$admin" :size="52" />
                <div class="min-w-0">
                    <div class="font-semibold truncate">{{ $admin->name }} <span class="text-xs font-normal text-slate-400">· admin</span></div>
                    <div class="text-xs text-slate-500">
                        <span class="px-1.5 py-0.5 rounded-full bg-brand-light text-brand-dark font-semibold">{{ $reputation['band'] }} · {{ $reputation['score'] }}</span>
                        @if ($adminRating['count'])· ⭐ {{ number_format($adminRating['average'], 1) }} ({{ $adminRating['count'] }})@else· no ratings yet @endif
                    </div>
                </div>
                <a href="{{ route('users.show', $admin->id) }}" class="ml-auto text-xs text-brand-dark hover:underline shrink-0">profile →</a>
            </div>
        @endif

        <div class="mt-6">
            @if (!$adashi->is_public)
                <div class="text-sm text-slate-500">🔒 This adashi is invitation-only. Ask the admin to add you.</div>
            @else
                <button type="button" onclick="document.getElementById('joinModal').classList.remove('hidden')"
                        class="rounded-lg bg-brand hover:bg-brand-dark text-white font-bold px-6 py-3">Join this adashi</button>
            @endif
        </div>
    </div>
    <p class="text-center text-xs text-slate-400 mt-3">Member details and the payout order are private until you join.</p>
</div>

@if ($adashi->is_public)
    <div id="joinModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
         onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
                <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                <span class="font-semibold">Join {{ $adashi->name }}</span>
                <button type="button" onclick="document.getElementById('joinModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" action="{{ route('adashi.join', $adashi->id) }}" class="p-5 space-y-3">
                @csrf
                <p class="text-sm text-slate-600">You'll be added at the next position in the rotation, contributing ₦{{ number_format($adashi->amount_per_cycle) }} per cycle.</p>
                @if ($adashi->rules)
                    <div class="rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600 px-3 py-2 whitespace-pre-line"><span class="font-semibold">Group rules:</span> {{ $adashi->rules }}</div>
                @endif
                <x-terms-notice variant="join" />
                <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Join adashi</button>
            </form>
        </div>
    </div>
@endif
