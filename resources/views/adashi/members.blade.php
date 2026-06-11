@extends('layouts.app')
@section('title', 'Manage Members')
@section('heading', 'Manage members')

@section('content')
    <a href="{{ route('adashi.show', $adashi->id) }}" class="text-sm text-brand-dark hover:underline">← Back to {{ $adashi->name }}</a>

    <div class="grid lg:grid-cols-3 gap-6 mt-4">
        {{-- Add member --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-1">Add a member</h3>
            <p class="text-sm text-slate-500 mb-4">Add by phone number. If they're not on KeenPocket yet, an account is created for them to claim later.</p>
            <form method="POST" action="{{ route('adashi.members.store', $adashi->id) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Phone number</label>
                    <input name="phone_number" value="{{ old('phone_number') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="08012345678">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Name <span class="text-slate-400 font-normal">(if new)</span></label>
                    <input name="name" value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="Member name">
                </div>
                <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Add member</button>
            </form>
        </div>

        {{-- Current members --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-3">Members ({{ $members->count() }})</h3>
            <ul class="divide-y divide-slate-100">
                @foreach ($members as $m)
                    <li class="py-3 flex items-center justify-between text-sm">
                        <span class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-xs">#{{ $m->position }}</span>
                            <span>
                                <span class="font-medium">{{ $m->name }}</span>
                                <span class="block text-xs text-slate-400">{{ $m->phone_number }}</span>
                            </span>
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $m->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $m->is_active ? 'active' : 'inactive' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
