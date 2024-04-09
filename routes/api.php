<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PurchaseOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::delete('purchase-orders/delete', [PurchaseOrderController::class, 'destroyMany']);
Route::resource('purchase-orders', PurchaseOrderController::class);

Route::resource('categories', CategoryController::class)->only(['index']);
