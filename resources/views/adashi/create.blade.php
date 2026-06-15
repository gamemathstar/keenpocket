@extends('layouts.app')
@section('title', 'New Adashi')
@section('heading', 'Create an Adashi')

@section('content')
    <div class="grid lg:grid-cols-3 gap-6 max-w-5xl">
        {{-- Intro / mascot panel --}}
        <aside class="bg-brand-light rounded-[2rem] border-b-8 border-brand p-6 text-center flex flex-col items-center lg:sticky lg:top-4 self-start">
            <img src="{{ asset('ant-k/kforadashi.png') }}" alt="" class="h-36 w-auto object-contain drop-shadow-xl mb-3">
            <h3 class="text-xl font-extrabold text-brand-dark">Create your Adashi</h3>
            <p class="text-sm text-slate-600 mt-1">Gather your friends, set your cycle, and start saving together. You can add members on the next step.</p>
            @if ($keens['enabled'] && !$keens['exempt'])
                <div class="mt-4 w-full rounded-2xl bg-white p-3 text-sm">
                    <div class="font-bold text-brand-dark">Costs <span id="keenCost">{{ $keens['base'] }}</span> 🪙</div>
                    <div class="text-xs text-slate-400">tiered by members · you have {{ number_format($keens['balance']) }} Keens</div>
                </div>
            @elseif ($keens['exempt'])
                <div class="mt-4 text-xs text-slate-500">✨ Super admins create for free.</div>
            @endif
        </aside>

        {{-- Form --}}
        <div class="lg:col-span-2 bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
            <form method="POST" action="{{ route('adashi.store') }}" class="space-y-6"
                  data-keen-base="{{ $keens['base'] }}" data-keen-tier="{{ $keens['tier'] }}" data-keen-step="{{ $keens['step'] }}">
                @csrf

                {{-- Details --}}
                <div>
                    <h3 class="flex items-center gap-2 font-extrabold text-brand-dark mb-3"><span>📝</span> Details</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Adashi name</label>
                            <input name="name" value="{{ old('name') }}" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="Family Adashi">
                        </div>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Amount per cycle (₦)</label>
                                <input type="number" name="amount_per_cycle" value="{{ old('amount_per_cycle', 50000) }}" min="1" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Expected members</label>
                                <input type="number" id="memberCap" name="member_capacity" value="{{ old('member_capacity', 12) }}" min="1" max="1000" class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                                <p class="text-xs text-slate-400 mt-1">Used to price creation. You add members next.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="border-t border-slate-100 pt-5">
                    <h3 class="flex items-center gap-2 font-extrabold text-brand-dark mb-3"><span>🗓️</span> Schedule</h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Cycle length (days)</label>
                            <input type="number" id="cycleDays" name="cycle_duration_days" value="{{ old('cycle_duration_days', 30) }}" min="1" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @foreach (['Daily' => 1, 'Every other day' => 2, 'Weekly' => 7, 'Bi-weekly' => 14, 'Monthly' => 30, 'Quarterly' => 91, 'Yearly' => 365] as $label => $days)
                                    <button type="button" onclick="document.getElementById('cycleDays').value={{ $days }}"
                                            class="text-xs rounded-full border border-slate-300 text-slate-600 hover:bg-brand-light hover:border-brand px-2.5 py-1">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Start date</label>
                                <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Rotation</label>
                                <select name="rotation_mode" class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:border-brand focus:ring-brand">
                                    <option value="MANUAL">Manual</option>
                                    <option value="AUTO">Auto</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payout details --}}
                <div class="border-t border-slate-100 pt-5">
                    <h3 class="flex items-center gap-2 font-extrabold text-brand-dark mb-3"><span>🏦</span> Payout details <span class="text-xs text-slate-400 font-normal">· optional</span></h3>
                    <div class="grid sm:grid-cols-3 gap-3">
                        <input name="account_name" value="{{ old('account_name') }}" class="rounded-xl border-2 border-slate-200 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account name">
                        <input name="bank" value="{{ old('bank') }}" class="rounded-xl border-2 border-slate-200 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Bank">
                        <input name="nuban" value="{{ old('nuban') }}" class="rounded-xl border-2 border-slate-200 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account number">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="is_public" value="1" class="rounded border-slate-300 text-brand focus:ring-brand">
                    List in the public directory (others can discover &amp; join)
                </label>
                <x-terms-notice variant="create" />
                <div class="flex gap-3 pt-1">
                    <button class="rounded-xl bg-brand hover:bg-brand-dark text-white font-bold px-5 py-2.5">Create adashi</button>
                    <a href="{{ route('adashi.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @if ($keens['enabled'] && !$keens['exempt'])
        <script>
            (function () {
                var form = document.querySelector('form[data-keen-base]');
                var cap = document.getElementById('memberCap');
                var out = document.getElementById('keenCost');
                if (!form || !cap || !out) return;
                var base = +form.dataset.keenBase, tier = +form.dataset.keenTier || 1, step = +form.dataset.keenStep;
                function update() {
                    var c = parseInt(cap.value, 10) || 0;
                    var units = c <= 0 ? 1 : Math.ceil(c / tier);
                    out.textContent = Math.max(0, base + step * (units - 1));
                }
                cap.addEventListener('input', update);
                update();
            })();
        </script>
    @endif
@endsection
