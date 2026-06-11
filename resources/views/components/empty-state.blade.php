@props(['title', 'message' => '', 'action' => '', 'actionLabel' => 'Get started'])

<div class="bg-white border border-slate-200 rounded-2xl p-8 text-center">
    <x-mascot :size="84" class="mx-auto mb-4" />
    <h3 class="font-extrabold text-lg">{{ $title }}</h3>
    @if ($message)
        <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">{{ $message }}</p>
    @endif
    @if ($action)
        <a href="{{ $action }}" class="inline-block mt-4 bg-brand text-white rounded-xl px-5 py-2.5">{{ $actionLabel }}</a>
    @endif
    {{ $slot }}
</div>
