@extends('layouts.app')
@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
    <div class="grid lg:grid-cols-2 gap-6 max-w-4xl">
        {{-- Account info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-1">Account information</h3>
            <p class="text-sm text-slate-500 mb-4">Update your name and contact details.</p>
            <form method="POST" action="{{ route('settings.profile') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Full name</label>
                    <input name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Phone number</label>
                    <input name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Save changes</button>
            </form>
        </div>

        {{-- Change password --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-1">Change password</h3>
            <p class="text-sm text-slate-500 mb-4">Use at least 6 characters.</p>
            <form method="POST" action="{{ route('settings.password') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Current password</label>
                    <input type="password" name="current_password" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">New password</label>
                    <input type="password" name="new_password" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Confirm new password</label>
                    <input type="password" name="new_password_confirmation" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Change password</button>
            </form>
        </div>
    </div>
@endsection
