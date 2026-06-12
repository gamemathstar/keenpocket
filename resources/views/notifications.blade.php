@extends('layouts.app')
@section('title', 'Notifications')
@section('heading', 'Notifications')

@section('content')
    <div class="flex items-center justify-between mb-5 max-w-2xl">
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-sm">
            <a href="{{ route('notifications.index') }}" class="px-3 py-1.5 rounded-md {{ $filter === 'all' ? 'bg-brand-light text-brand-dark font-medium' : 'text-slate-500' }}">All</a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="px-3 py-1.5 rounded-md {{ $filter === 'unread' ? 'bg-brand-light text-brand-dark font-medium' : 'text-slate-500' }}">Unread {{ $unreadCount ? '('.$unreadCount.')' : '' }}</a>
        </div>
        @if ($unreadCount)
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
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('notifications.open', $n->id) }}" class="font-medium text-sm hover:underline">{{ $n->title }}</a>
                        @if ($n->type)<span class="text-[10px] uppercase tracking-wide text-slate-400">{{ $n->type }}</span>@endif
                    </div>
                    <p class="text-sm text-slate-600 mt-0.5">{{ $n->body }}</p>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-xs text-slate-400">{{ $n->created_at?->diffForHumans() }}</span>
                        @if ($n->status === 'Not Read')
                            <form method="POST" action="{{ route('notifications.readOne', $n->id) }}">
                                @csrf
                                <button class="text-xs text-brand-dark hover:underline">Mark read</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="px-4 py-10 text-center">
                <x-mascot :size="72" class="mx-auto mb-3" />
                <p class="text-sm text-slate-500">{{ $filter === 'unread' ? "You're all caught up!" : 'No notifications yet.' }}</p>
            </div>
        @endforelse
    </div>
@endsection
