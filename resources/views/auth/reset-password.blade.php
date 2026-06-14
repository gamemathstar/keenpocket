@extends('layouts.guest')
@section('title', 'Set a new password — KeenPocket')

@section('content')
    <h1 class="text-xl font-semibold mb-1">Set a new password</h1>
    <p class="text-sm text-slate-500 mb-6">Choose a new password for your KeenPocket account.</p>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $email) }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand"
                   placeholder="you@example.com">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">New password</label>
            <input type="password" name="password" required autofocus
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand"
                   placeholder="••••••••">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Confirm new password</label>
            <input type="password" name="password_confirmation" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand"
                   placeholder="••••••••">
        </div>
        <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5 transition">Reset password</button>
    </form>

    <p class="text-sm text-center text-slate-500 mt-6">
        <a href="{{ route('login') }}" class="text-brand-dark font-medium hover:underline">Back to sign in</a>
    </p>
@endsection
