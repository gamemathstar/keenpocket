<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\FirebaseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [AgentController::class,"index"])->name("agent.index");
Route::get('/edit', [AgentController::class,"edit"])->name("agent.edit");
Route::post('/save', [AgentController::class,"save"])->name("agent.save");
Route::get('/ajax/load/ward', [AgentController::class,"loadWard"])->name("ajax.load.ward");
Route::get('/ajax/load/unit', [AgentController::class,"loadUnit"])->name("ajax.load.unit");

// Firebase service account upload
Route::get('/firebase/upload', [FirebaseController::class, 'showUploadForm']);
Route::post('/firebase/upload', [FirebaseController::class, 'handleUpload']);

