@props(['user', 'size' => 40])
@php
    $name = is_object($user) ? ($user->name ?? 'U') : (string) $user;
    $avatar = is_object($user) ? ($user->avatar ?? null) : null;
    $px = (int) $size;
@endphp
@if ($avatar)
    <img src="{{ \Illuminate\Support\Str::startsWith($avatar, ['http://', 'https://']) ? $avatar : asset('storage/'.$avatar) }}"
         alt="{{ $name }}" class="rounded-full object-cover shrink-0" style="height: {{ $px }}px; width: {{ $px }}px"
         {{ $attributes }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full bg-brand-light text-brand-dark font-extrabold shrink-0']) }}
          style="height: {{ $px }}px; width: {{ $px }}px; font-size: {{ max(11, (int) ($px * 0.4)) }}px">
        {{ strtoupper(substr($name, 0, 1)) }}
    </span>
@endif
