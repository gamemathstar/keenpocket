<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/keenpocket-icon.svg') }}">
    <title>@yield('title', 'KeenPocket')</title>
    @include('partials.styles')
</head>
<body class="h-full">
<div class="max-w-3xl mx-auto px-4 py-10">
    <a href="{{ url('/') }}" class="inline-block"><img src="{{ asset('images/keenpocket-lockup.svg') }}" alt="KeenPocket" class="h-10"></a>
    <div class="bg-white rounded-2xl border border-slate-200 p-8 mt-5 text-sm leading-relaxed text-slate-700 space-y-4">
        @yield('content')
    </div>
    <p class="text-center text-xs text-slate-400 mt-5">
        <a href="{{ route('terms') }}" class="hover:underline">Terms</a> ·
        <a href="{{ route('privacy') }}" class="hover:underline">Privacy</a> ·
        <a href="{{ url('/') }}" class="hover:underline">Home</a>
    </p>
</div>
</body>
</html>
