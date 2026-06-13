<?php

use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\PocketController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\GamificationController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReputationController;
use App\Http\Controllers\WalletController;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Adashi\AdashiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//Public Routes — auth endpoints are rate limited to slow brute-force / spam.
Route::middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Token flows used by the mobile client (API_REFERENCE §2.3, §2.5, §2.6).
    Route::post('refresh-token', [AuthController::class, 'refreshToken']);
    Route::get('request-token', [AuthController::class, 'requestToken']);
    Route::post('verify-token', [AuthController::class, 'verifyToken']);

    // Phone OTP verification (no-op while OTP_ENABLED=false — see config/otp.php).
    Route::get('otp/status', [OtpController::class, 'status']);
    Route::post('otp/request', [OtpController::class, 'request']);
    Route::post('otp/verify', [OtpController::class, 'verify']);
});

// Payment gateway webhook — public (gateways are unauthenticated callers), but
// the request signature is verified inside PaymentService before any settlement.
Route::post('payments/webhook/{provider}', [PaymentController::class, 'webhook']);

// Payout (transfer) webhook — public, signature-verified inside PayoutService.
Route::post('payouts/webhook/{provider}', [PayoutController::class, 'webhook']);

// Legacy one-off polling-unit importer. Unauthenticated bulk-write: local only.
Route::get('test', function (Request $request) {
    abort_unless(app()->environment('local'), 404);
    $arr = [];
    foreach ($request[0]['records'] as $record) {
        $state_id = 36;
        if (isset($record['lga'])) {
            $lga = Lga::where("name", $record['lga'])->first();
            if (!$lga) {
                $lga = new Lga();
                $lga->name = $record['lga'];
                $lga->state_id = $state_id;
                $lga->save();
            }

            $ward = Ward::where("name", $record['ward'])->first();
            if (!$ward) {
                $ward = new Ward();
                $ward->name = $record['ward'];
                $ward->lga_id = $lga->id;
                $ward->save();
            }
            $field = "polling unit name";
            $units = explode(";", $record[$field]);
            foreach ($units as $unit) {
                $arr[] = ["name" => $unit, "ward_id" => $ward->id];
            }
        }


    }
    PollingUnit::upsert($arr, ["name", "ward_id"], ["name", "ward_id"]);
    return $request;
});


//Protected Routes
Route::group(["middleware" => ['auth:sanctum']], function () {
    Route::get('logout', [AuthController::class, 'logout']);
    Route::post('change-password', [AuthController::class, 'changePassword']);

    Route::get('dashboard', [PocketController::class, 'dashboard']);
    Route::get('invoice', [InvoiceController::class, 'invoice']);
    Route::get('my-pockets', [PocketController::class, 'myPockets']);
    Route::get('search-pocket', [PocketController::class, 'search']);
    Route::get('search-user', [FeedController::class, 'searchUser']);
    Route::get('pocket', [PocketController::class, 'pocket']);
    Route::get('pocket/invoices', [InvoiceController::class, 'pocketInvoices']);
    Route::get('pocket/charity', [\App\Http\Controllers\Api\CharityController::class, 'show']);
    Route::post('pocket/charity/donate', [\App\Http\Controllers\Api\CharityController::class, 'donate']);
    Route::post('pocket/charity/setup', [\App\Http\Controllers\Api\CharityController::class, 'setup']);
    Route::get('pocket/month/invoices/', [InvoiceController::class, 'pocketMonthInvoices']);
    Route::post('pocket/members/add-keen', [PocketController::class, 'addKeen']);

    Route::get('posts/', [FeedController::class, 'posts']);
    Route::get('post/', [FeedController::class, 'post']);
    Route::get('notifications/', [FeedController::class, 'notifications']);

    Route::post('create/pocket', [PocketController::class, 'createPocket']);
    Route::post('pocket/add/bank/detail', [PocketController::class, 'addBankDetails']);
    Route::post('pocket/join', [PocketController::class, 'joinPocket']);
    Route::post('invite/user', [PocketController::class, 'inviteUser']);
    Route::post('pocket/switch', [PocketController::class, 'pocketSwitch']);
    Route::post('pocket/selection/switch', [PocketController::class, 'openSelection']);
    Route::post('invoice/create', [InvoiceController::class, 'createInvoice']);
    Route::post('push/notification/update', [FeedController::class, 'savePushNotificationToken']);
    Route::post('invitation/cancel', [PocketController::class, 'cancelInvitation']);
    Route::post('request/accept', [PocketController::class, 'acceptRequest']);
    Route::post('add/payment/item', [ItemController::class, 'addPaymentItem']);
    Route::post('remove/payment/item', [ItemController::class, 'removePaymentItem']);
    Route::post('add/shopping/item', [ItemController::class, 'addShoppingItem']);
    Route::post('remove/shopping/item', [ItemController::class, 'removeShoppingItem']);
    Route::post('subscribe/shopping/item', [ItemController::class, 'subscribeShoppingItem']);
    Route::post('payment/status/update', [InvoiceController::class, 'changePaymentStatus']);

    // Online payments (no-op while PAYMENTS_ENABLED=false — see config/payments.php).
    Route::get('payments/status', [PaymentController::class, 'status']);
    Route::post('payments/initialize', [PaymentController::class, 'initialize']);
    Route::get('payments/verify', [PaymentController::class, 'verify']);

    // Automated payouts (no-op while PAYOUTS_ENABLED=false — see config/payouts.php).
    Route::get('payouts/status', [PayoutController::class, 'status']);
    Route::post('payouts/bank-account', [PayoutController::class, 'saveBankAccount']);
    Route::post('adashi/{id}/payout', [PayoutController::class, 'initiate']);

    // Referral / WhatsApp invite growth loop (see config/referrals.php).
    Route::get('referrals/me', [ReferralController::class, 'me']);
    Route::get('referrals', [ReferralController::class, 'index']);

    // Identity verification (no-op while KYC_ENABLED=false — see config/kyc.php).
    Route::get('kyc/status', [KycController::class, 'status']);
    Route::post('kyc/submit', [KycController::class, 'submit'])->middleware('throttle:auth');

    // Discovery: member reputation + public directory of joinable pockets.
    Route::get('reputation/me', [ReputationController::class, 'me']);
    Route::get('users/{id}/reputation', [ReputationController::class, 'show']);
    Route::get('directory/pockets', [DirectoryController::class, 'pockets']);
    Route::get('directory/adashi', [DirectoryController::class, 'adashi']);

    // Peer trust ratings.
    Route::post('ratings', [RatingController::class, 'store']);
    Route::get('users/{id}/ratings', [RatingController::class, 'forUser']);

    // Gamification: streaks + achievement badges.
    Route::get('gamification/me', [GamificationController::class, 'me']);
    Route::get('users/{id}/badges', [GamificationController::class, 'badges']);

    // In-app wallet (no-op while WALLET_ENABLED=false — see config/wallet.php).
    Route::get('wallet', [WalletController::class, 'balance']);
    Route::get('wallet/history', [WalletController::class, 'history']);
    Route::post('wallet/topup', [WalletController::class, 'topup']);
    Route::post('wallet/pay-invoice', [WalletController::class, 'payInvoice']);

    // Adashi endpoints
    Route::prefix('adashi')->group(function () {
        Route::post('/', [AdashiController::class, 'create']);
        Route::get('dashboard', [AdashiController::class, 'dashboard']);
        Route::get('search', [AdashiController::class, 'search']);
        Route::get('{id}', [AdashiController::class, 'show']);
        Route::post('{id}/join', [AdashiController::class, 'join']);
        Route::post('{id}/contribute', [AdashiController::class, 'contribute']);
        Route::post('{id}/reconcile', [AdashiController::class, 'reconcilePayments']);
        Route::get('{id}/records', [AdashiController::class, 'records']);
        Route::post('{id}/next-cycle', [AdashiController::class, 'nextCycle']);
        Route::post('{id}/auto-rotate', [AdashiController::class, 'rotate']);
        Route::post('{id}/visibility', [AdashiController::class, 'setVisibility']);
        Route::post('{id}/admin/override', [AdashiController::class, 'adminOverride']);
        Route::get('{id}/members/{memberId}/contributors', [AdashiController::class, 'contributorsIndex']);
        Route::post('{id}/members/{memberId}/contributors', [AdashiController::class, 'contributorsStore']);
    });
});
