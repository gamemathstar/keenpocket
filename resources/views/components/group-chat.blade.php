@props(['type', 'id', 'messages', 'canPost' => false])

<div id="chat" class="bg-white rounded-xl border border-slate-200 p-5 mt-6">
    <h3 class="font-semibold mb-3">💬 Group chat</h3>

    <div class="space-y-3 max-h-96 overflow-y-auto mb-3 pr-1" id="chatScroll">
        @forelse ($messages as $m)
            @php $mine = $m->user_id == auth()->id(); @endphp
            <div class="flex {{ $mine ? 'justify-end' : '' }}">
                <div class="max-w-[80%]">
                    <div class="text-[11px] text-slate-400 mb-0.5 {{ $mine ? 'text-right' : '' }}">
                        {{ $mine ? 'You' : $m->name }} · {{ \Illuminate\Support\Carbon::parse($m->created_at)->diffForHumans(null, true) }} ago
                    </div>
                    <div class="text-sm rounded-2xl px-3 py-2 {{ $mine ? 'bg-brand text-white rounded-br-sm' : 'bg-slate-100 text-slate-800 rounded-bl-sm' }}">{{ $m->body }}</div>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500 py-4 text-center">No messages yet — say hello 👋</p>
        @endforelse
    </div>

    @if ($canPost)
        <form method="POST" action="{{ route('chat.post', [$type, $id]) }}" class="flex gap-2 border-t border-slate-100 pt-3">
            @csrf
            <input name="body" required maxlength="1000" autocomplete="off"
                   class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Message the group…">
            <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Send</button>
        </form>
    @else
        <p class="text-sm text-slate-500 border-t border-slate-100 pt-3">Only members can post here.</p>
    @endif

    <script>
        // Keep the chat scrolled to the newest message.
        (function () {
            var s = document.getElementById('chatScroll');
            if (s) s.scrollTop = s.scrollHeight;
        })();
    </script>
</div>
