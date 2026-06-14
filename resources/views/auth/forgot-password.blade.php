@extends('layouts.guest')
@section('title', 'Reset your password — KeenPocket')

@section('content')
    <h1 class="text-xl font-semibold mb-1">Forgot your password?</h1>
    <p class="text-sm text-slate-500 mb-6">Enter the email on your account and we'll send you a link to set a new password.</p>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand"
                   placeholder="you@example.com">
        </div>
        <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5 transition">Send reset link</button>
    </form>

    <p class="text-sm text-center text-slate-500 mt-6">
        <a href="{{ route('login') }}" class="text-brand-dark font-medium hover:underline">Back to sign in</a>
    </p>
@endsection
