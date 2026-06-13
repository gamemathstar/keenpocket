@extends('layouts.app')
@section('title', 'Vouch requests')
@section('heading', 'Vouch requests')

@section('content')
    <p class="text-sm text-slate-500 mb-6">People who named you as their guarantor. Recommend someone you trust — the pocket admin then makes the final decision.</p>

    <div class="bg-white rounded-xl border border-slate-200 p-5 max-w-3xl">
        <ul class="divide-y divide-slate-100">
            @forelse ($requests as $r)
                <li class="py-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <a href="{{ route('pockets.show', $r->pocket_id) }}" class="font-medium hover:underline">{{ $r->requester }}</a>
                        <span class="text-sm text-slate-400">wants to join “{{ $r->pocket_title }}”</span>
                    </div>
                    @if ($r->status === 'PENDING')
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('guarantor.recommend', $r->id) }}">@csrf<button class="text-xs rounded-md bg-brand hover:bg-brand-dark text-white px-3 py-1.5">Recommend</button></form>
                            <form method="POST" action="{{ route('guarantor.decline', $r->id) }}">@csrf<button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-3 py-1.5">Decline</button></form>
                        </div>
                    @elseif ($r->status === 'RECOMMENDED')
                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">recommended</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">declined</span>
                    @endif
                </li>
            @empty
                <li class="py-8 text-center text-sm text-slate-500">No vouch requests right now.</li>
            @endforelse
        </ul>
    </div>
@endsection
