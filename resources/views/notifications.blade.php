@extends('layouts.app')
@section('title', 'Notifications')
@section('heading', 'Notifications')

@section('content')
    <div class="flex items-center justify-between mb-5 max-w-2xl">
        <p class="text-slate-500 text-sm">Updates from your pockets and adashi groups.</p>
        @if ($notifications->where('status', 'Not Read')->count())
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button class="text-sm rounded-lg border border-slate-300 hover:bg-slate-50 px-3 py-1.5">Mark all as read</button>
            </form>
        @endif
    </div>

    <div class="max-w-2xl bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
        @forelse ($notifications as $n)
            <div class="px-4 py-3 flex gap-3 {{ $n->status === 'Not Read' ? 'bg-brand-light/30' : '' }}">
                <span class="mt-1.5 h-2 w-2 rounded-full shrink-0 {{ $n->status === 'Not Read' ? 'bg-brand' : 'bg-transparent' }}"></span>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-sm">{{ $n->title }}</span>
                        @if ($n->type)<span class="text-[10px] uppercase tracking-wide text-slate-400">{{ $n->type }}</span>@endif
                    </div>
                    <p class="text-sm text-slate-600 mt-0.5">{{ $n->body }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ $n->created_at?->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-sm text-slate-500">🔔 No notifications yet.</div>
        @endforelse
    </div>
@endsection
