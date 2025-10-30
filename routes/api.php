<?php

use App\Http\Controllers\APIController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
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
//Public Routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('test', function (Request $request) {
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
    Route::get('users', [Controller::class, 'index']);
    Route::get('logout', [AuthController::class, 'logout']);

    Route::get('dashboard', [APIController::class, 'dashboard']);
    Route::get('invoice', [APIController::class, 'invoice']);
    Route::get('my-pockets', [APIController::class, 'myPockets']);
    Route::get('search-pocket', [APIController::class, 'search']);
    Route::get('search-user', [APIController::class, 'searchUser']);
    Route::get('pocket', [APIController::class, 'pocket']);
    Route::get('pocket/invoices', [APIController::class, 'pocketInvoices']);
    Route::get('pocket/month/invoices/', [APIController::class, 'pocketMonthInvoices']);
    Route::post('pocket/members/add-keen', [APIController::class, 'addKeen']);

    Route::get('posts/', [APIController::class, 'posts']);
    Route::get('post/', [APIController::class, 'post']);
    Route::get('notifications/', [APIController::class, 'notifications']);

    Route::post('create/pocket', [APIController::class, 'createPocket']);
    Route::post('pocket/add/bank/detail', [APIController::class, 'addBankDetails']);
    Route::post('pocket/join', [APIController::class, 'joinPocket']);
    Route::post('invite/user', [APIController::class, 'inviteUser']);
    Route::post('pocket/switch', [APIController::class, 'pocketSwitch']);
    Route::post('pocket/selection/switch', [APIController::class, 'openSelection']);
    Route::post('invoice/create', [APIController::class, 'createInvoice']);
    Route::post('push/notification/update', [APIController::class, 'savePushNotificationToken']);
    Route::post('invitation/cancel', [APIController::class, 'cancelInvitation']);
    Route::post('request/accept', [APIController::class, 'acceptRequest']);
    Route::post('add/payment/item', [APIController::class, 'addPaymentItem']);
    Route::post('remove/payment/item', [APIController::class, 'removePaymentItem']);
    Route::post('add/shopping/item', [APIController::class, 'addShoppingItem']);
    Route::post('remove/shopping/item', [APIController::class, 'removeShoppingItem']);
    Route::post('subscribe/shopping/item', [APIController::class, 'subscribeShoppingItem']);
    Route::post('payment/status/update', [APIController::class, 'changePaymentStatus']);

    // Adashi endpoints
    Route::prefix('adashi')->group(function () {
        Route::post('/', [AdashiController::class, 'create']);
        Route::get('{id}', [AdashiController::class, 'show']);
        Route::post('{id}/join', [AdashiController::class, 'join']);
        Route::get('{id}/records', [AdashiController::class, 'records']);
        Route::post('{id}/next-cycle', [AdashiController::class, 'nextCycle']);
        Route::get('{id}/members/{memberId}/contributors', [AdashiController::class, 'contributorsIndex']);
        Route::post('{id}/members/{memberId}/contributors', [AdashiController::class, 'contributorsStore']);
    });
});
