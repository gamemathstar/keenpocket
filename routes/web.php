<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\Web\AdashiWebController;
use App\Http\Controllers\Web\AuthController as WebAuth;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DiscoverController;
use App\Http\Controllers\Web\InvoiceController;
use App\Http\Controllers\Web\KycWebController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PayoutsController;
use App\Http\Controllers\Web\PocketController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ReferralWebController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\WalletWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — KeenPocket Blade interface (session auth)
|--------------------------------------------------------------------------
*/

// Landing → app or login.
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

// Guest (session) auth.
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuth::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuth::class, 'login']);
    Route::get('/register', [WebAuth::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuth::class, 'register']);
});

// Authenticated app.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuth::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/pockets', [PocketController::class, 'index'])->name('pockets.index');
    Route::get('/pockets/create', [PocketController::class, 'create'])->name('pockets.create');
    Route::post('/pockets', [PocketController::class, 'store'])->name('pockets.store');
    Route::get('/pockets/{id}', [PocketController::class, 'show'])->name('pockets.show');
    Route::post('/pockets/{id}/join', [PocketController::class, 'join'])->name('pockets.join');
    Route::post('/pockets/{id}/rate-admin', [PocketController::class, 'rateAdmin'])->name('pockets.rateAdmin');
    Route::post('/pockets/{id}/payout-account', [PocketController::class, 'setAccount'])->name('pockets.setAccount');
    Route::get('/search', [\App\Http\Controllers\Web\SearchController::class, 'index'])->name('search');
    Route::get('/pockets/{id}/manage', [PocketController::class, 'manage'])->name('pockets.manage');
    Route::get('/pockets/{id}/invoices/export', [PocketController::class, 'exportInvoices'])->name('pockets.invoices.export');
    Route::post('/pockets/{id}/members', [PocketController::class, 'addMember'])->name('pockets.addMember');
    Route::post('/pockets/{id}/members/accept', [PocketController::class, 'acceptMember'])->name('pockets.acceptMember');
    Route::post('/pockets/{id}/members/decline', [PocketController::class, 'declineMember'])->name('pockets.declineMember');
    Route::post('/pockets/{id}/guarantor/toggle', [PocketController::class, 'toggleGuarantor'])->name('pockets.guarantorToggle');
    Route::get('/vouches', [\App\Http\Controllers\Web\GuarantorController::class, 'requests'])->name('guarantor.requests');
    Route::post('/vouches/{id}/recommend', [\App\Http\Controllers\Web\GuarantorController::class, 'recommend'])->name('guarantor.recommend');
    Route::post('/vouches/{id}/decline', [\App\Http\Controllers\Web\GuarantorController::class, 'decline'])->name('guarantor.decline');
    Route::post('/pockets/{id}/toggle', [PocketController::class, 'toggleStatus'])->name('pockets.toggleStatus');
    Route::post('/pockets/{id}/account-details', [PocketController::class, 'saveBankDetails'])->name('pockets.account');
    Route::post('/pockets/{id}/selection/toggle', [PocketController::class, 'toggleSelection'])->name('pockets.selection');
    Route::post('/pockets/{id}/members-visibility', [PocketController::class, 'toggleMembersVisibility'])->name('pockets.membersVisibility');

    // Invoices / contributions
    Route::get('/pockets/{id}/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/pockets/{id}/invoices/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::post('/pockets/{id}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('/invoices/{id}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.markPaid');
    Route::post('/invoices/{id}/pay-wallet', [InvoiceController::class, 'payWallet'])->name('invoices.payWallet');

    // Pocket shopping list (group buying)
    Route::post('/pockets/{id}/shopping', [\App\Http\Controllers\Web\ShoppingController::class, 'store'])->name('shopping.store');
    Route::post('/shopping/{id}/delete', [\App\Http\Controllers\Web\ShoppingController::class, 'destroy'])->name('shopping.destroy');

    // Charity drive (Sadaqah / fi-sabilillah)
    Route::get('/pockets/{id}/charity/setup', [\App\Http\Controllers\Web\CharityController::class, 'setup'])->name('charity.setup');
    Route::post('/pockets/{id}/charity', [\App\Http\Controllers\Web\CharityController::class, 'store'])->name('charity.store');
    Route::post('/pockets/{id}/charity/donate', [\App\Http\Controllers\Web\CharityController::class, 'donate'])->name('charity.donate');

    // In-group chat (pocket / adashi members)
    Route::post('/chat/{type}/{id}', [\App\Http\Controllers\Web\ChatController::class, 'post'])->name('chat.post');

    // Home planning (monthly grocery plans, shared & collaborative)
    Route::get('/plans', [\App\Http\Controllers\Web\PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [\App\Http\Controllers\Web\PlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [\App\Http\Controllers\Web\PlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{id}', [\App\Http\Controllers\Web\PlanController::class, 'show'])->name('plans.show');
    Route::post('/plans/{id}/items', [\App\Http\Controllers\Web\PlanController::class, 'storeItem'])->name('plans.items.store');
    Route::post('/plan-items/{itemId}/update', [\App\Http\Controllers\Web\PlanController::class, 'updateItem'])->name('plans.items.update');
    Route::post('/plan-items/{itemId}/delete', [\App\Http\Controllers\Web\PlanController::class, 'destroyItem'])->name('plans.items.destroy');
    Route::post('/plans/{id}/share', [\App\Http\Controllers\Web\PlanController::class, 'share'])->name('plans.share');
    Route::post('/plans/{id}/unshare/{userId}', [\App\Http\Controllers\Web\PlanController::class, 'unshare'])->name('plans.unshare');
    Route::post('/plans/{id}/archive', [\App\Http\Controllers\Web\PlanController::class, 'archive'])->name('plans.archive');

    Route::get('/adashi', [AdashiWebController::class, 'index'])->name('adashi.index');
    Route::get('/adashi/create', [AdashiWebController::class, 'create'])->name('adashi.create');
    Route::post('/adashi', [AdashiWebController::class, 'store'])->name('adashi.store');
    Route::get('/adashi/{id}', [AdashiWebController::class, 'show'])->name('adashi.show');
    Route::post('/adashi/{id}/contribute', [AdashiWebController::class, 'contribute'])->name('adashi.contribute');
    Route::post('/adashi/{id}/contributions/add', [AdashiWebController::class, 'addContribution'])->name('adashi.contribution.add');
    Route::post('/adashi/contributions/{invoiceId}/verify', [AdashiWebController::class, 'verifyContribution'])->name('adashi.contribution.verify');
    Route::post('/adashi/contributions/{invoiceId}/decline', [AdashiWebController::class, 'declineContribution'])->name('adashi.contribution.decline');
    Route::post('/adashi/{id}/reconcile', [AdashiWebController::class, 'reconcile'])->name('adashi.reconcile');
    Route::get('/adashi/{id}/records/export', [AdashiWebController::class, 'exportRecords'])->name('adashi.records.export');
    Route::get('/adashi/{id}/members', [AdashiWebController::class, 'membersForm'])->name('adashi.members');
    Route::post('/adashi/{id}/members', [AdashiWebController::class, 'addMember'])->name('adashi.members.store');
    Route::post('/adashi/{id}/admin', [AdashiWebController::class, 'adminAction'])->name('adashi.admin');
    Route::post('/adashi/{id}/bank', [AdashiWebController::class, 'saveBank'])->name('adashi.bank');
    Route::post('/adashi/{id}/rate-admin', [AdashiWebController::class, 'rateAdmin'])->name('adashi.rateAdmin');
    Route::post('/adashi/{id}/payout-account', [AdashiWebController::class, 'setAccount'])->name('adashi.setAccount');
    Route::post('/adashi/{id}/payout-visibility', [AdashiWebController::class, 'togglePayoutVisibility'])->name('adashi.payoutVisibility');

    Route::get('/discover', [DiscoverController::class, 'index'])->name('discover');
    Route::get('/leaderboard', [\App\Http\Controllers\Web\LeaderboardController::class, 'index'])->name('leaderboard');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'readOne'])->name('notifications.readOne');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');

    Route::get('/payouts', [PayoutsController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/bank-account', [PayoutsController::class, 'saveBankAccount'])->name('payouts.saveBank');
    Route::post('/pockets/{id}/bank', [PayoutsController::class, 'savePocketBank'])->name('payouts.savePocketBank');

    Route::get('/wallet', [WalletWebController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [WalletWebController::class, 'topup'])->name('wallet.topup');
    Route::get('/referrals', [ReferralWebController::class, 'index'])->name('referrals.index');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::get('/users/{id}', [\App\Http\Controllers\Web\PublicProfileController::class, 'show'])->name('users.show');
    Route::post('/kyc', [KycWebController::class, 'submit'])->name('kyc.submit');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/avatar', [SettingsController::class, 'updateAvatar'])->name('settings.avatar');
    Route::post('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences');
    Route::post('/settings/accounts', [SettingsController::class, 'storeAccount'])->name('settings.accounts.store');
    Route::post('/settings/accounts/{id}/default', [SettingsController::class, 'defaultAccount'])->name('settings.accounts.default');
    Route::post('/settings/accounts/{id}/delete', [SettingsController::class, 'deleteAccount'])->name('settings.accounts.delete');
});

/*
| Local-only tooling.
|
| The legacy polling-agent admin UI and the Firebase service-account upload
| form perform unauthenticated writes / handle credentials, so they must
| never be reachable in production. They remain available in `local` for
| development and one-time setup.
*/
if (app()->environment('local')) {
    // Legacy polling-agent tooling (not part of the savings product).
    Route::get('/agents', [AgentController::class, 'index'])->name('agent.index');
    Route::get('/agents/edit', [AgentController::class, 'edit'])->name('agent.edit');
    Route::post('/agents/save', [AgentController::class, 'save'])->name('agent.save');
    Route::get('/ajax/load/ward', [AgentController::class, 'loadWard'])->name('ajax.load.ward');
    Route::get('/ajax/load/unit', [AgentController::class, 'loadUnit'])->name('ajax.load.unit');

    // Firebase service account upload (one-time setup convenience).
    Route::get('/firebase/upload', [FirebaseController::class, 'showUploadForm']);
    Route::post('/firebase/upload', [FirebaseController::class, 'handleUpload']);
}
