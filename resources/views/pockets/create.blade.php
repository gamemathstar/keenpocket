@extends('layouts.app')
@section('title', 'New Pocket')
@section('heading', 'Create a Pocket')

@section('content')
    <div class="grid lg:grid-cols-3 gap-6 max-w-5xl">
        {{-- Intro / mascot panel --}}
        <aside class="bg-brand-light rounded-[2rem] border-b-8 border-brand p-6 text-center flex flex-col items-center lg:sticky lg:top-4 self-start">
            <img src="{{ asset('ant-k/kforpocket.png') }}" alt="" class="h-36 w-auto object-contain drop-shadow-xl mb-3">
            <h3 class="text-xl font-extrabold text-brand-dark">Ready for a new pocket?</h3>
            <p class="text-sm text-slate-600 mt-1">Pockets are community-driven savings groups where everyone gets a turn. Let's set up your circle!</p>
            @if ($keens['enabled'] && !$keens['exempt'])
                <div class="mt-4 w-full rounded-2xl bg-white p-3 text-sm">
                    <div class="font-bold text-brand-dark">Costs <span id="keenCost">{{ $keens['base'] }}</span> 🪙</div>
                    <div class="text-xs text-slate-400">tiered by max members · you have {{ number_format($keens['balance']) }} Keens</div>
                </div>
            @elseif ($keens['exempt'])
                <div class="mt-4 text-xs text-slate-500">✨ Super admins create for free.</div>
            @endif
        </aside>

        {{-- Form --}}
        <div class="lg:col-span-2 bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
            <form method="POST" action="{{ route('pockets.store') }}" class="space-y-6"
                  data-keen-base="{{ $keens['base'] }}" data-keen-tier="{{ $keens['tier'] }}" data-keen-step="{{ $keens['step'] }}">
                @csrf

                {{-- General details --}}
                <div>
                    <h3 class="flex items-center gap-2 font-extrabold text-brand-dark mb-3"><span>📝</span> General details</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Title</label>
                            <input name="title" value="{{ old('title') }}" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="2026 Ramadan Pocket">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Description</label>
                            <textarea name="description" rows="2" class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="What's the goal for this group?">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="border-t border-slate-100 pt-5">
                    <h3 class="flex items-center gap-2 font-extrabold text-brand-dark mb-3"><span>🗓️</span> Timeline</h3>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Year</label>
                            <input type="number" name="year" value="{{ old('year', date('Y')) }}" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Start month</label>
                            <input type="number" name="start_month" value="{{ old('start_month', 1) }}" min="1" max="12" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Months</label>
                            <input type="number" name="month_count" value="{{ old('month_count', 12) }}" min="1" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                </div>

                {{-- Contribution --}}
                <div class="border-t border-slate-100 pt-5">
                    <h3 class="flex items-center gap-2 font-extrabold text-brand-dark mb-3"><span>💰</span> Contribution</h3>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Amount per hand (₦)</label>
                            <input type="number" name="amount_per_hand" value="{{ old('amount_per_hand', 5000) }}" min="1" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Max members <span class="text-slate-400 font-normal">(0 = ∞)</span></label>
                            <input type="number" id="maxKeens" name="max_keens" value="{{ old('max_keens', 0) }}" min="0" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Your hands</label>
                            <input type="number" name="hand_count" value="{{ old('hand_count', 1) }}" min="1" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                </div>

                <x-terms-notice variant="create" />
                <div class="flex gap-3 pt-1">
                    <button class="rounded-xl bg-brand hover:bg-brand-dark text-white font-bold px-5 py-2.5">Create pocket</button>
                    <a href="{{ route('pockets.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @if ($keens['enabled'] && !$keens['exempt'])
        <script>
            (function () {
                var form = document.querySelector('form[data-keen-base]');
                var max = document.getElementById('maxKeens');
                var out = document.getElementById('keenCost');
                if (!form || !max || !out) return;
                var base = +form.dataset.keenBase, tier = +form.dataset.keenTier || 1, step = +form.dataset.keenStep;
                function update() {
                    var cap = parseInt(max.value, 10) || 0;
                    var units = cap <= 0 ? 1 : Math.ceil(cap / tier);
                    out.textContent = Math.max(0, base + step * (units - 1));
                }
                max.addEventListener('input', update);
                update();
            })();
        </script>
    @endif
@endsection
