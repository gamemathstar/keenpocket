@extends('layouts.app')
@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
    {{-- Hero --}}
    <section class="bg-brand-light rounded-[2rem] border-b-8 border-brand p-6 sm:p-7 mb-6 max-w-4xl flex items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-dark leading-tight">Settings ⚙️</h2>
            <p class="text-slate-600 mt-1">Manage your profile, security, notifications and payout accounts.</p>
        </div>
        <x-mascot :size="80" class="hidden sm:block drop-shadow-xl" />
    </section>

    {{-- Profile photo --}}
    <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6 mb-6 max-w-4xl">
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
    <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6 mb-6 max-w-4xl">
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
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
            <h3 class="font-semibold mb-1">Account information</h3>
            <p class="text-sm text-slate-500 mb-4">Update your name and contact details.</p>
            <form method="POST" action="{{ route('settings.profile') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Full name</label>
                    <input name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email <span class="text-slate-400 font-normal">· locked</span></label>
                    <input type="email" value="{{ $user->email }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-100 text-slate-500 px-3 py-2 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Phone number <span class="text-slate-400 font-normal">· locked</span></label>
                    <input value="{{ $user->phone_number }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-100 text-slate-500 px-3 py-2 cursor-not-allowed">
                </div>
                <p class="text-xs text-slate-400">Your email and phone number are tied to your account and can't be changed here. Contact support if they're wrong.</p>
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Save changes</button>
            </form>
        </div>

        {{-- Change password --}}
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
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

        {{-- Bank accounts --}}
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
            <h3 class="font-semibold mb-1">Bank accounts</h3>
            <p class="text-sm text-slate-500 mb-4">Save the accounts you receive payouts into. You pick which one to use for each pocket or adashi.</p>

            <ul class="divide-y divide-slate-100 mb-4">
                @forelse ($user->bankAccounts as $acc)
                    <li class="py-3 flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-medium">
                                {{ $acc->account_name }}
                                @if ($acc->is_default)<span class="text-xs px-2 py-0.5 rounded-full bg-brand-light text-brand-dark ml-1">default</span>@endif
                            </div>
                            <div class="text-xs text-slate-400">{{ $acc->label ? $acc->label.' · ' : '' }}{{ $acc->bank }} · <span class="font-mono">{{ $acc->nuban }}</span></div>
                        </div>
                        <div class="flex items-center gap-2">
                            @unless ($acc->is_default)
                                <form method="POST" action="{{ route('settings.accounts.default', $acc->id) }}">@csrf<button class="text-xs text-brand-dark hover:underline">make default</button></form>
                            @endunless
                            <form method="POST" action="{{ route('settings.accounts.delete', $acc->id) }}" onsubmit="return confirm('Remove this account?')">@csrf<button class="text-xs text-red-500 hover:underline">remove</button></form>
                        </div>
                    </li>
                @empty
                    <li class="py-3 text-sm text-slate-500">No accounts saved yet.</li>
                @endforelse
            </ul>

            <form method="POST" action="{{ route('settings.accounts.store') }}" class="grid sm:grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                @csrf
                <input name="account_name" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account name">
                <input name="label" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Label (e.g. Salary) — optional">
                <input name="bank" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Bank">
                <input name="nuban" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account number">
                <label class="flex items-center gap-2 text-sm text-slate-600 sm:col-span-2">
                    <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300 text-brand focus:ring-brand"> Make this my default
                </label>
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5 sm:col-span-2 justify-self-start">Add account</button>
            </form>
        </div>
    </div>
@endsection
