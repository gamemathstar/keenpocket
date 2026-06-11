<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KeenPocket')</title>
    @include('partials.styles')
</head>
<body class="h-full">
<div class="min-h-full flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="flex items-center justify-center gap-2 mb-6">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand text-white font-bold text-lg">K</span>
            <span class="font-semibold text-xl">KeenPocket</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </div>
        <p class="text-center text-xs text-slate-400 mt-6">Save together — pockets &amp; adashi made easy.</p>
    </div>
</div>
</body>
</html>
