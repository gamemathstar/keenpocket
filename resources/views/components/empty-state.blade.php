@props(['title', 'message' => '', 'action' => '', 'actionLabel' => 'Get started'])

<div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-8 text-center">
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
