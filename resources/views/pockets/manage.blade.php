@extends('layouts.app')
@section('title', 'Manage Pocket')
@section('heading', 'Manage pocket')

@section('content')
    <a href="{{ route('pockets.show', $pocket->id) }}" class="text-sm text-brand-dark hover:underline">← Back to {{ $pocket->title }}</a>

    <div class="flex items-center justify-between bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 mt-4 max-w-5xl">
        <div>
            <div class="font-semibold">{{ $pocket->title }}</div>
            <div class="text-sm text-slate-500">{{ $pocket->status ? 'Open — anyone can join' : 'Closed — invitation only' }}</div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pockets.invoices.export', $pocket->id) }}" class="rounded-lg border border-slate-300 hover:bg-slate-50 px-4 py-2 text-sm">⬇ Export invoices (CSV)</a>
            <form method="POST" action="{{ route('pockets.guarantorToggle', $pocket->id) }}">
                @csrf
                <button class="rounded-lg border border-slate-300 hover:bg-slate-50 px-4 py-2 text-sm">
                    {{ $pocket->guarantor_required ? '🤝 Guarantor: on' : '🤝 Guarantor: off' }}
                </button>
            </form>
            <form method="POST" action="{{ route('pockets.membersVisibility', $pocket->id) }}">
                @csrf
                <button class="rounded-lg border border-slate-300 hover:bg-slate-50 px-4 py-2 text-sm">
                    {{ $pocket->members_visible ? '👀 Hands: visible' : '🙈 Hands: private' }}
                </button>
            </form>
            <form method="POST" action="{{ route('pockets.toggleStatus', $pocket->id) }}">
                @csrf
                <button class="rounded-lg border border-slate-300 hover:bg-slate-50 px-4 py-2 text-sm">
                    {{ $pocket->status ? 'Close pocket' : 'Open pocket' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Pending join requests --}}
    @if ($requests->isNotEmpty())
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6 mt-6 max-w-5xl">
            <h3 class="font-semibold mb-3">Join requests ({{ $requests->count() }})</h3>
            <ul class="divide-y divide-slate-100">
                @foreach ($requests as $r)
                    @php $g = $guarantors[$r->slot_id] ?? null; @endphp
                    <li class="py-3 flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div>
                            <span class="font-medium">{{ $r->name }}</span>
                            <span class="text-xs text-slate-400">· {{ $r->phone_number }} · {{ (int) $r->hand_count }} hand(s)</span>
                            @if ($pocket->guarantor_required)
                                @if ($g && $g->status === 'RECOMMENDED')
                                    <span class="block text-xs text-emerald-600">🤝 Guarantor recommended</span>
                                @elseif ($g && $g->status === 'DECLINED')
                                    <span class="block text-xs text-red-500">🤝 Guarantor declined</span>
                                @elseif ($g)
                                    <span class="block text-xs text-amber-600">🤝 Awaiting guarantor recommendation</span>
                                @else
                                    <span class="block text-xs text-amber-600">No guarantor named</span>
                                @endif
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('pockets.acceptMember', $pocket->id) }}">
                                @csrf
                                <input type="hidden" name="slot_id" value="{{ $r->slot_id }}">
                                <button class="text-xs rounded-md bg-brand hover:bg-brand-dark text-white px-3 py-1.5">Accept</button>
                            </form>
                            <form method="POST" action="{{ route('pockets.declineMember', $pocket->id) }}">
                                @csrf
                                <input type="hidden" name="slot_id" value="{{ $r->slot_id }}">
                                <button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-3 py-1.5">Decline</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6 mt-6 max-w-5xl">
        {{-- Add member --}}
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
            <h3 class="font-semibold mb-1">Add a member</h3>
            <p class="text-sm text-slate-500 mb-4">Add by phone. New numbers get a placeholder account to claim later.</p>
            <form method="POST" action="{{ route('pockets.addMember', $pocket->id) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Phone number</label>
                    <input name="phone_number" value="{{ old('phone_number') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="08012345678">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Name <span class="text-slate-400 font-normal">(if new)</span></label>
                    <input name="name" value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Hands</label>
                    <input type="number" name="hand_count" value="1" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <x-terms-notice variant="add" />
                <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Add member</button>
            </form>
        </div>

        {{-- Collection account --}}
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
            <h3 class="font-semibold mb-1">Collection account</h3>
            <p class="text-sm text-slate-500 mb-4">Where members send contributions.</p>
            <form method="POST" action="{{ route('pockets.account', $pocket->id) }}" class="space-y-3">
                @csrf
                <input name="account_name" value="{{ old('account_name', $pocket->account_name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account name">
                <input name="bank" value="{{ old('bank', $pocket->bank) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Bank">
                <input name="nuban" value="{{ old('nuban', $pocket->nuban) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account number">
                <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Save account</button>
            </form>
        </div>

        {{-- Members --}}
        <div class="lg:col-span-2 bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
            <h3 class="font-semibold mb-3">Members ({{ $members->where('status', 1)->count() }})</h3>
            <ul class="divide-y divide-slate-100">
                @foreach ($members as $m)
                    <li class="py-3 flex items-center justify-between text-sm">
                        <span>
                            <span class="font-medium">{{ $m->name }}</span>
                            <span class="block text-xs text-slate-400">{{ $m->phone_number }}</span>
                        </span>
                        <span class="flex items-center gap-3">
                            <span class="text-slate-500">{{ (int) $m->hand_count }} hand(s)</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $m->status ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $m->status ? 'active' : 'pending' }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
