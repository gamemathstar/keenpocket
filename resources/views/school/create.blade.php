@extends('layouts.app')
@section('title', 'Create school')
@section('heading', 'Create your school')

@section('content')
    @if (file_exists(public_path('ant-k/kforgoing.png')))
        <div class="max-w-2xl rounded-2xl overflow-hidden border border-slate-200 mb-4">
            <img src="{{ asset('ant-k/kforgoing.png') }}" alt="K taking his child to school" class="w-full h-40 sm:h-48 object-cover object-center">
        </div>
    @endif
    <div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
        <p class="text-sm text-slate-500 mb-4">Set up your school to manage class fees, students and parent payments. KeenPocket keeps the records — it never holds your money.</p>
        <form method="POST" action="{{ route('school.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">School name</label>
                <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="K International School">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Address</label>
                    <input name="address" value="{{ old('address') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Contact (phone/email)</label>
                    <input name="contact" value="{{ old('contact') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
            </div>
            <div class="grid sm:grid-cols-3 gap-4 border-t border-slate-100 pt-4">
                <input name="account_name" value="{{ old('account_name') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account name">
                <input name="bank" value="{{ old('bank') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Bank">
                <input name="nuban" value="{{ old('nuban') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account number">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                @php $fileClass = 'w-full text-sm text-slate-500 rounded-lg border border-slate-300 file:mr-3 file:border-0 file:bg-brand-light file:text-brand-dark file:font-semibold file:px-4 file:py-2.5 file:cursor-pointer hover:file:bg-brand/20 cursor-pointer'; @endphp
                <div>
                    <label class="block text-sm font-medium mb-1">Logo <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="file" name="logo" accept="image/*" class="{{ $fileClass }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Background image <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="file" name="background_image" accept="image/*" class="{{ $fileClass }}">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Create school</button>
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
@endsection
