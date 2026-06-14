@extends('layouts.legal')
@section('title', 'Privacy Policy — KeenPocket')

@section('content')
    <h1 class="text-2xl font-extrabold text-slate-900">Privacy Policy</h1>
    <p class="text-xs text-slate-400">Last updated {{ date('F Y') }}</p>

    <h2 class="font-bold text-slate-900 pt-2">1. What we collect</h2>
    <p>Your name, phone number, email, username and (optionally) a profile photo; the groups you create or join; and the contribution, donation and fee <strong>records</strong> you or your admins enter. We do not collect card or bank-login details, because we never process payments.</p>

    <h2 class="font-bold text-slate-900 pt-2">2. How we use it</h2>
    <p>To run the service — show your groups, track records, send you reminders and notifications, and keep the app secure. We do not sell your personal data.</p>

    <h2 class="font-bold text-slate-900 pt-2">3. What others can see</h2>
    <p>Group admins and fellow members of a pocket or adashi can see shared group activity. Individual charity donations are private (visible only to you and the admin) unless the admin publishes a donor list. People who are not members see only a limited public summary of a group.</p>

    <h2 class="font-bold text-slate-900 pt-2">4. Sharing</h2>
    <p>We share data only with service providers that help us operate (e.g. hosting, messaging/notification providers) and where required by law. Where you choose to share to WhatsApp or copy a link, that content leaves the app at your direction.</p>

    <h2 class="font-bold text-slate-900 pt-2">5. Your rights</h2>
    <p>You may request access to, correction of, or deletion of your personal data, subject to records we must keep. Contact us through the app. We handle personal data in line with applicable Nigerian data-protection law (NDPR).</p>

    <h2 class="font-bold text-slate-900 pt-2">6. Security & retention</h2>
    <p>We take reasonable measures to protect your data and keep it only as long as needed to provide the service and meet legal obligations.</p>

    <p class="text-xs text-slate-400 pt-4">See also our <a href="{{ route('terms') }}" class="text-brand-dark hover:underline">Terms of Use</a>.</p>
@endsection
