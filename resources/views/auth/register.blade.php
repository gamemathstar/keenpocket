@extends('layouts.guest')
@section('title', 'Create account — KeenPocket')

@section('content')
    <h1 class="text-xl font-semibold mb-1">Create your account</h1>
    <p class="text-sm text-slate-500 mb-6">Start saving with your circle.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Full name</label>
            <input name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Phone number</label>
            <input name="phone_number" value="{{ old('phone_number') }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand"
                   placeholder="09087654321">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Confirm</label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
            </div>
        </div>
        <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5 transition">Create account</button>
    </form>

    <p class="text-sm text-center text-slate-500 mt-6">
        Already have an account? <a href="{{ route('login') }}" class="text-brand-dark font-medium hover:underline">Sign in</a>
    </p>
@endsection
