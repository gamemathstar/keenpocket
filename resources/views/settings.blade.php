@extends('layouts.app')
@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
    {{-- Profile photo --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6 max-w-4xl">
        <h3 class="font-semibold mb-3">Profile photo</h3>
        <form method="POST" action="{{ route('settings.avatar') }}" enctype="multipart/form-data" class="flex items-center gap-4">
            @csrf
            <x-avatar :user="$user" :size="72" />
            <div class="flex-1">
                <input type="file" name="avatar" accept="image/*" required
                       class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-light file:px-4 file:py-2 file:text-brand-dark file:font-bold">
                <p class="text-xs text-slate-400 mt-1">JPG/PNG, up to 2&nbsp;MB.</p>
            </div>
            <button class="bg-brand text-white rounded-xl px-5 py-2.5">Upload</button>
        </form>
    </div>

    {{-- Notification preferences --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6 max-w-4xl">
        <h3 class="font-semibold mb-1">Notification preferences</h3>
        <p class="text-sm text-slate-500 mb-4">Choose how you'd like to receive payment reminders & updates.</p>
        <form method="POST" action="{{ route('settings.preferences') }}" class="space-y-3">
            @csrf
            @php $prefs = [['notify_push','Push notifications','📱'],['notify_sms','SMS','✉️'],['notify_whatsapp','WhatsApp','💬']]; @endphp
            @foreach ($prefs as [$key, $label, $icon])
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3">
                    <span class="font-bold">{{ $icon }} {{ $label }}</span>
                    <input type="checkbox" name="{{ $key }}" value="1" @checked($user->$key) class="h-5 w-5 rounded border-slate-300 text-brand focus:ring-brand">
                </label>
            @endforeach
            <button class="rounded-lg bg-brand text-white font-medium px-5 py-2.5">Save preferences</button>
        </form>
    </div>

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
