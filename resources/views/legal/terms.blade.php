@extends('layouts.legal')
@section('title', 'Terms of Use — KeenPocket')

@section('content')
    <h1 class="text-2xl font-extrabold text-slate-900">Terms of Use</h1>
    <p class="text-xs text-slate-400">Last updated {{ date('F Y') }}</p>

    <h2 class="font-bold text-slate-900 pt-2">1. What KeenPocket is</h2>
    <p>KeenPocket is a <strong>record-keeping tool</strong> for community savings (pockets), rotating savings (adashi), shopping plans and school fees. It is <strong>not a bank, deposit-taker, or financial institution</strong>, and it is not licensed to hold or transmit money.</p>

    <h2 class="font-bold text-slate-900 pt-2">2. We never hold your money</h2>
    <p>KeenPocket does <strong>not receive, hold, store, transfer, or have access to any funds</strong>. All contributions, payouts, donations and fees move <strong>directly between you and the group's admin or members</strong>, offline or through your own bank — never through KeenPocket. Amounts shown in the app are records that members and admins enter themselves.</p>

    <h2 class="font-bold text-slate-900 pt-2">3. Deal only with people you trust</h2>
    <p>You should only join a pocket or adashi, or accept a school's terms, where you <strong>personally know and trust the admin</strong>. KeenPocket does not vet, guarantee, or stand behind any group, admin, member, school or payment.</p>

    <h2 class="font-bold text-slate-900 pt-2">4. No liability</h2>
    <p>KeenPocket is provided "as is." To the fullest extent permitted by law, KeenPocket and its operators are <strong>not responsible</strong> for any contribution, payout, donation, fee, default, dispute, loss, or disagreement between users. Any dispute is between the parties involved; the in-app dispute feature is a record only.</p>

    <h2 class="font-bold text-slate-900 pt-2">5. Your responsibilities</h2>
    <p>Keep your login details secure, provide accurate information, and only record genuine transactions. Admins are responsible for collecting and disbursing funds with their members directly and for the accuracy of the records they keep.</p>

    <h2 class="font-bold text-slate-900 pt-2">6. Keens</h2>
    <p>"Keens" are in-app credits used to access certain features (such as creating groups). They have <strong>no cash value</strong>, are non-refundable, and cannot be exchanged for money.</p>

    <h2 class="font-bold text-slate-900 pt-2">7. Changes & contact</h2>
    <p>We may update these terms; continued use means you accept the changes. Questions? Contact us through the app.</p>

    <p class="text-xs text-slate-400 pt-4">By using KeenPocket you agree to these terms and to our <a href="{{ route('privacy') }}" class="text-brand-dark hover:underline">Privacy Policy</a>.</p>
@endsection
