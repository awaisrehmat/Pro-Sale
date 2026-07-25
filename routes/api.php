<?php
use App\Http\Controllers\Api\{AuthController,OperationsController,PartyController,ProductController,TransactionController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login',[AuthController::class,'login']);
Route::middleware('auth:sanctum')->group(function(){
    Route::post('logout',[AuthController::class,'logout']); Route::get('user',fn(Request $r)=>response()->json(['success'=>true,'message'=>'User retrieved.','data'=>$r->user()]));
    Route::get('dashboard',[OperationsController::class,'dashboard']);
    Route::apiResource('products',ProductController::class); Route::get('products/{product}/stock-movements',[ProductController::class,'movements']);
    Route::get('suppliers',[PartyController::class,'suppliers']); Route::post('suppliers',[PartyController::class,'storeSupplier']); Route::get('suppliers/{supplier}',[PartyController::class,'supplier']); Route::put('suppliers/{supplier}',[PartyController::class,'updateSupplier']); Route::get('suppliers/{supplier}/ledger',[PartyController::class,'supplierLedger']);
    Route::get('customers',[PartyController::class,'customers']); Route::post('customers',[PartyController::class,'storeCustomer']); Route::get('customers/{customer}',[PartyController::class,'customer']); Route::put('customers/{customer}',[PartyController::class,'updateCustomer']); Route::get('customers/{customer}/ledger',[PartyController::class,'customerLedger']);
    Route::get('purchases',[TransactionController::class,'purchases']); Route::post('purchases',[TransactionController::class,'createPurchase']); Route::get('purchases/{purchase}',[TransactionController::class,'purchase']); Route::post('purchases/{purchase}/cancel',[TransactionController::class,'cancelPurchase']);
    Route::get('sales',[TransactionController::class,'sales']); Route::post('sales',[TransactionController::class,'createSale']); Route::get('sales/{sale}',[TransactionController::class,'sale']); Route::post('sales/{sale}/cancel',[TransactionController::class,'cancelSale']);
    Route::get('stock-movements',[OperationsController::class,'movements']); Route::post('stock-adjustments',[OperationsController::class,'adjust']);
    Route::get('payments',[OperationsController::class,'payments']); Route::post('payments',[OperationsController::class,'pay']); Route::get('payments/outstanding',[OperationsController::class,'outstanding']); Route::get('payments/{payment}',[OperationsController::class,'payment']);
    Route::get('reports/{type}',[OperationsController::class,'report'])->whereIn('type',['stock','low-stock','purchases','sales','profit']);
});
