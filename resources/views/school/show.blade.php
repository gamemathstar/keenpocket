@extends('layouts.app')
@section('title', $school->name)
@section('heading', 'School')

@section('content')
    {{-- Header --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-6">
        @if ($school->background_image)
            <div class="h-28 bg-slate-100"><img src="{{ asset('storage/'.$school->background_image) }}" class="w-full h-full object-cover" alt=""></div>
        @endif
        <div class="p-5 flex items-center gap-4">
            @if ($school->logo)
                <img src="{{ asset('storage/'.$school->logo) }}" class="h-14 w-14 rounded-xl object-cover border border-slate-200" alt="">
            @endif
            <div>
                <h2 class="text-2xl font-semibold">{{ $school->name }}</h2>
                <p class="text-xs text-slate-400">{{ $school->address }}@if($school->contact) · {{ $school->contact }}@endif</p>
                @if ($school->nuban)<p class="text-xs text-slate-500">Fees to: {{ $school->account_name }} · {{ $school->bank }} · <span class="font-mono">{{ $school->nuban }}</span></p>@endif
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Classes & per-term fees --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-3">Classes &amp; fees</h3>
            @forelse ($classes as $class)
                <div class="border border-slate-100 rounded-lg p-3 mb-3">
                    <div class="font-medium">{{ $class->name }} <span class="text-xs text-slate-400">· {{ $class->students_count }} student(s)</span></div>
                    <div class="grid grid-cols-3 gap-2 mt-2 text-xs">
                        @foreach ([1,2,3] as $t)
                            <div class="rounded bg-slate-50 p-2">
                                <div class="text-slate-400">Term {{ $t }}</div>
                                <div class="font-semibold">₦{{ number_format($class->termFee($t)) }}</div>
                                @foreach ($class->feeItems->where('term', $t) as $fi)
                                    <div class="text-slate-500 truncate">{{ $fi->name }}: ₦{{ number_format($fi->amount) }}</div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 mb-3">No classes yet.</p>
            @endforelse

            <form method="POST" action="{{ route('school.classes.store', $school->id) }}" class="flex gap-2 border-t border-slate-100 pt-3 mb-3">
                @csrf
                <input name="name" required placeholder="Add a class (e.g. JSS1)" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                <button class="btn-soft text-sm">Add class</button>
            </form>

            @if ($classes->isNotEmpty())
                <form method="POST" action="{{ route('school.fees.store', $school->id) }}" class="grid grid-cols-2 sm:grid-cols-4 gap-2 border-t border-slate-100 pt-3">
                    @csrf
                    <select name="school_class_id" class="rounded-lg border border-slate-300 px-2 py-2 text-sm focus:border-brand focus:ring-brand">
                        @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                    <select name="term" class="rounded-lg border border-slate-300 px-2 py-2 text-sm focus:border-brand focus:ring-brand">
                        <option value="1">Term 1</option><option value="2">Term 2</option><option value="3">Term 3</option>
                    </select>
                    <input name="name" required placeholder="Fee item" class="rounded-lg border border-slate-300 px-2 py-2 text-sm focus:border-brand focus:ring-brand">
                    <input type="number" name="amount" min="0" required placeholder="₦" class="rounded-lg border border-slate-300 px-2 py-2 text-sm focus:border-brand focus:ring-brand">
                    <button class="btn-soft text-sm sm:col-span-4 justify-center">Add fee item</button>
                </form>
            @endif
        </div>

        {{-- Students --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-3">Students ({{ $students->count() }})</h3>
            <ul class="divide-y divide-slate-100 mb-3 max-h-72 overflow-y-auto">
                @forelse ($students as $st)
                    <li class="py-2 text-sm flex justify-between">
                        <span>{{ $st->name }} <span class="text-xs text-slate-400">· {{ $st->class_name ?? 'no class' }}</span></span>
                        <span class="text-xs text-slate-400">{{ $st->parent_name }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">No students yet.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('school.students.store', $school->id) }}" class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                @csrf
                <input name="name" required placeholder="Student name" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                <select name="school_class_id" class="rounded-lg border border-slate-300 px-2 py-2 text-sm focus:border-brand focus:ring-brand">
                    <option value="">— class —</option>
                    @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
                <input name="parent_phone" required placeholder="Parent phone" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                <input name="parent_name" placeholder="Parent name (if new)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                <button class="btn-soft text-sm col-span-2 justify-center">Add student</button>
            </form>
        </div>
    </div>

    @if ($students->isNotEmpty())
        <div class="grid lg:grid-cols-2 gap-6 mt-6">
            {{-- Record a payment --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="font-semibold mb-3">Record a payment</h3>
                <form method="POST" action="{{ route('school.payments.store', $school->id) }}" class="grid grid-cols-2 gap-2">
                    @csrf
                    <select name="student_id" class="col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                        @foreach ($students as $st)<option value="{{ $st->id }}">{{ $st->name }} ({{ $st->parent_name }})</option>@endforeach
                    </select>
                    <select name="term" class="rounded-lg border border-slate-300 px-2 py-2 text-sm focus:border-brand focus:ring-brand">
                        <option value="1">Term 1</option><option value="2">Term 2</option><option value="3">Term 3</option>
                    </select>
                    <input type="number" name="amount" min="1" required placeholder="Amount ₦" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                    <button class="btn-soft text-sm col-span-2 justify-center">Record payment</button>
                </form>
            </div>

            {{-- Set a payment plan --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="font-semibold mb-3">Set a payment plan</h3>
                <form method="POST" action="{{ route('school.plan', $school->id) }}" class="grid grid-cols-2 gap-2">
                    @csrf
                    <select name="student_id" class="col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                        @foreach ($students as $st)<option value="{{ $st->id }}">{{ $st->name }} ({{ $st->parent_name }})</option>@endforeach
                    </select>
                    <select name="mode" onchange="document.getElementById('planPercent').classList.toggle('hidden', this.value!=='percent'); document.getElementById('planMin').classList.toggle('hidden', this.value!=='min_monthly');" class="rounded-lg border border-slate-300 px-2 py-2 text-sm focus:border-brand focus:ring-brand">
                        <option value="percent">% of term fee</option>
                        <option value="min_monthly">Min ₦ / month</option>
                    </select>
                    <select name="percent" id="planPercent" class="rounded-lg border border-slate-300 px-2 py-2 text-sm focus:border-brand focus:ring-brand">
                        <option value="100">100%</option><option value="50">50%</option><option value="30">30%</option>
                    </select>
                    <input type="number" name="min_monthly" id="planMin" min="1" placeholder="Min ₦/month per child" class="hidden col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                    <button class="btn-soft text-sm col-span-2 justify-center">Save plan</button>
                </form>
                <p class="text-xs text-slate-400 mt-2">e.g. ₦10,000/month per child until the term fee is cleared.</p>
            </div>
        </div>
    @endif
@endsection
