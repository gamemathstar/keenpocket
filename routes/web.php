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
    Route::get('/search', [\App\Http\Controllers\Web\SearchController::class, 'index'])->name('search');
    Route::get('/pockets/{id}/manage', [PocketController::class, 'manage'])->name('pockets.manage');
    Route::get('/pockets/{id}/invoices/export', [PocketController::class, 'exportInvoices'])->name('pockets.invoices.export');
    Route::post('/pockets/{id}/members', [PocketController::class, 'addMember'])->name('pockets.addMember');
    Route::post('/pockets/{id}/toggle', [PocketController::class, 'toggleStatus'])->name('pockets.toggleStatus');

    // Invoices / contributions
    Route::get('/pockets/{id}/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/pockets/{id}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('/invoices/{id}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.markPaid');
    Route::post('/invoices/{id}/pay-wallet', [InvoiceController::class, 'payWallet'])->name('invoices.payWallet');

    // Pocket shopping list (group buying)
    Route::post('/pockets/{id}/shopping', [\App\Http\Controllers\Web\ShoppingController::class, 'store'])->name('shopping.store');
    Route::post('/shopping/{id}/delete', [\App\Http\Controllers\Web\ShoppingController::class, 'destroy'])->name('shopping.destroy');

    Route::get('/adashi', [AdashiWebController::class, 'index'])->name('adashi.index');
    Route::get('/adashi/create', [AdashiWebController::class, 'create'])->name('adashi.create');
    Route::post('/adashi', [AdashiWebController::class, 'store'])->name('adashi.store');
    Route::get('/adashi/{id}', [AdashiWebController::class, 'show'])->name('adashi.show');
    Route::post('/adashi/{id}/contribute', [AdashiWebController::class, 'contribute'])->name('adashi.contribute');
    Route::post('/adashi/{id}/reconcile', [AdashiWebController::class, 'reconcile'])->name('adashi.reconcile');
    Route::get('/adashi/{id}/records/export', [AdashiWebController::class, 'exportRecords'])->name('adashi.records.export');
    Route::get('/adashi/{id}/members', [AdashiWebController::class, 'membersForm'])->name('adashi.members');
    Route::post('/adashi/{id}/members', [AdashiWebController::class, 'addMember'])->name('adashi.members.store');
    Route::post('/adashi/{id}/admin', [AdashiWebController::class, 'adminAction'])->name('adashi.admin');

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
