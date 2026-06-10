<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\FirebaseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| KeenPocket is an API-first application (the mobile client talks to
| routes/api.php). The web surface is intentionally minimal in production.
|
*/

// Public health check / landing.
Route::get('/', function () {
    return response()->json([
        'app' => config('app.name'),
        'status' => 'ok',
    ]);
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
