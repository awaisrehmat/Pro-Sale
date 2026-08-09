<?php

use App\Http\Controllers\Api\{
    AuthController,
    CompanySettingsController,
    OperationsController,
    PartyController,
    PdfController,
    ProductController,
    ProductCategoryController,
    TransactionController,
    UserAdministrationController
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::get('company-profile', [CompanySettingsController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', function (Request $request) {
        $user = $request->user()->load('roles:id,name');
        return response()->json([
            'success' => true,
            'message' => 'User retrieved.',
            'data' => ['user' => $user, 'permissions' => $user->getAllPermissions()->pluck('name')],
        ]);
    });

    Route::get('dashboard', [OperationsController::class, 'dashboard'])->middleware('permission:dashboard.view');

    Route::middleware('permission:products.view')->group(function () {
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{product}', [ProductController::class, 'show']);
        Route::get('products/{product}/stock-movements', [ProductController::class, 'movements']);
    });
    Route::middleware('permission:products.manage')->group(function () {
        Route::post('products', [ProductController::class, 'store']);
        Route::match(['put', 'patch'], 'products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);
    });

    Route::middleware('permission:suppliers.view')->group(function () {
        Route::get('suppliers', [PartyController::class, 'suppliers']);
        Route::get('suppliers/{supplier}', [PartyController::class, 'supplier']);
        Route::get('suppliers/{supplier}/ledger', [PartyController::class, 'supplierLedger']);
    });
    Route::middleware('permission:suppliers.manage')->group(function () {
        Route::post('suppliers', [PartyController::class, 'storeSupplier']);
        Route::put('suppliers/{supplier}', [PartyController::class, 'updateSupplier']);
    });

    Route::middleware('permission:customers.view')->group(function () {
        Route::get('customers', [PartyController::class, 'customers']);
        Route::get('customers/{customer}', [PartyController::class, 'customer']);
        Route::get('customers/{customer}/ledger', [PartyController::class, 'customerLedger']);
    });
    Route::middleware('permission:customers.manage')->group(function () {
        Route::post('customers', [PartyController::class, 'storeCustomer']);
        Route::put('customers/{customer}', [PartyController::class, 'updateCustomer']);
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('purchases', [TransactionController::class, 'purchases']);
        Route::get('purchases/{purchase}', [TransactionController::class, 'purchase']);
        Route::get('purchases/{purchase}/pdf', [PdfController::class, 'purchase']);
    });
    Route::post('purchases', [TransactionController::class, 'createPurchase'])->middleware('permission:purchases.create');
    Route::post('purchases/{purchase}/cancel', [TransactionController::class, 'cancelPurchase'])->middleware('permission:purchases.cancel');

    Route::middleware('permission:sales.view')->group(function () {
        Route::get('sales', [TransactionController::class, 'sales']);
        Route::get('sales/{sale}', [TransactionController::class, 'sale']);
        Route::get('sales/{sale}/pdf', [PdfController::class, 'sale']);
    });
    Route::post('sales', [TransactionController::class, 'createSale'])->middleware('permission:sales.create');
    Route::post('sales/{sale}/cancel', [TransactionController::class, 'cancelSale'])->middleware('permission:sales.cancel');

    Route::get('stock-movements', [OperationsController::class, 'movements'])->middleware('permission:stock.view');
    Route::post('stock-adjustments', [OperationsController::class, 'adjust'])->middleware('permission:stock.adjust');

    Route::middleware('permission:payments.view')->group(function () {
        Route::get('payments', [OperationsController::class, 'payments']);
        Route::get('payments/outstanding', [OperationsController::class, 'outstanding'])->middleware('permission:payments.create');
        Route::get('payments/{payment}', [OperationsController::class, 'payment']);
        Route::get('payments/{payment}/pdf', [PdfController::class, 'voucher']);
    });
    Route::post('payments', [OperationsController::class, 'pay'])->middleware('permission:payments.create');

    Route::get('reports/{type}', [OperationsController::class, 'report'])
        ->whereIn('type', ['stock', 'low-stock', 'purchases', 'sales', 'profit'])
        ->middleware('permission:reports.view');

    Route::middleware('permission:users.manage')->prefix('users')->group(function () {
        Route::get('/', [UserAdministrationController::class, 'index']);
        Route::get('roles', [UserAdministrationController::class, 'roles']);
        Route::post('/', [UserAdministrationController::class, 'store']);
        Route::put('{user}', [UserAdministrationController::class, 'update']);
    });

    Route::get('company-settings', [CompanySettingsController::class, 'show'])->middleware('permission:settings.manage');
    Route::put('company-settings', [CompanySettingsController::class, 'update'])->middleware('permission:settings.manage');
    Route::post('company-settings/logo', [CompanySettingsController::class, 'uploadLogo'])->middleware('permission:settings.manage');
    Route::delete('company-settings/logo', [CompanySettingsController::class, 'removeLogo'])->middleware('permission:settings.manage');

    Route::get('product-categories', [ProductCategoryController::class, 'index'])->middleware('permission:settings.manage|products.manage');
    Route::middleware('permission:settings.manage')->prefix('product-categories')->group(function () {
        Route::post('/', [ProductCategoryController::class, 'storeCategory']);
        Route::put('{category}', [ProductCategoryController::class, 'updateCategory']);
        Route::delete('{category}', [ProductCategoryController::class, 'destroyCategory']);
        Route::post('subcategories', [ProductCategoryController::class, 'storeSubcategory']);
        Route::put('subcategories/{subcategory}', [ProductCategoryController::class, 'updateSubcategory']);
        Route::delete('subcategories/{subcategory}', [ProductCategoryController::class, 'destroySubcategory']);
    });
});
