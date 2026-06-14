@extends('layouts.guest')
@section('title', 'Sign in — KeenPocket')

@section('content')
    <h1 class="text-xl font-semibold mb-1">Welcome back</h1>
    <p class="text-sm text-slate-500 mb-6">Sign in to your KeenPocket account.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Phone, email or username</label>
            <input name="login" value="{{ old('login') }}" required autofocus
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand"
                   placeholder="09087654321 · you@example.com · username">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand"
                   placeholder="••••••••">
        </div>
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand focus:ring-brand"> Remember me
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-brand-dark hover:underline">Forgot password?</a>
        </div>
        <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5 transition">Sign in</button>
    </form>

    <p class="text-sm text-center text-slate-500 mt-6">
        New here? <a href="{{ route('register') }}" class="text-brand-dark font-medium hover:underline">Create an account</a>
    </p>
@endsection
