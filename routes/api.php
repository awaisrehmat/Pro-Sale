<?php

use App\Http\Controllers\Api\{
    AuthController,
    CompanySettingsController,
    CompanyAdministrationController,
    ExpenseController,
    OperationsController,
    PartyController,
    PdfController,
    ProductController,
    ProductCategoryController,
    TransactionController,
    UserAdministrationController,
    UnitOfMeasurementController
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::get('company-profile', [CompanySettingsController::class, 'publicProfile']);

Route::middleware(['auth:sanctum','company'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', function (Request $request) {
        $user = $request->user()->load('roles:id,name');
        $companies=$user->companies()->where('companies.is_active',true)->orderBy('companies.name')->get(['companies.id','companies.name','companies.code']);
        $permissions=$user->getAllPermissions()->pluck('name')->when(!$user->is_group_admin,fn($items)=>$items->reject(fn($name)=>in_array($name,['companies.manage','reports.consolidated'])))->values();
        return response()->json([
            'success' => true,
            'message' => 'User retrieved.',
            'data' => ['user' => $user, 'permissions' => $permissions,'companies'=>$companies,'current_company'=>$request->attributes->get('company'),'company_profile'=>\App\Models\Setting::company()],
        ]);
    });

    Route::get('companies', [CompanyAdministrationController::class, 'index']);
    Route::post('companies', [CompanyAdministrationController::class, 'store'])->middleware('permission:companies.manage');
    Route::put('companies/{company}', [CompanyAdministrationController::class, 'update'])->middleware('permission:companies.manage');

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
        Route::get('payment-vouchers', [OperationsController::class, 'paymentVouchers']);
        Route::get('payment-vouchers/{payment}', [OperationsController::class, 'paymentVoucher']);
        Route::get('payment-vouchers/{payment}/pdf', [PdfController::class, 'paymentVoucher']);
        Route::get('receipt-vouchers', [OperationsController::class, 'receiptVouchers']);
        Route::get('receipt-vouchers/{payment}', [OperationsController::class, 'receiptVoucher']);
        Route::get('receipt-vouchers/{payment}/pdf', [PdfController::class, 'receiptVoucher']);
        Route::get('payments', [OperationsController::class, 'payments']);
        Route::get('payments/outstanding', [OperationsController::class, 'outstanding'])->middleware('permission:payments.create');
        Route::get('payments/context', [OperationsController::class, 'paymentContext'])->middleware('permission:payments.create');
        Route::get('payments/{payment}', [OperationsController::class, 'payment']);
        Route::get('payments/{payment}/pdf', [PdfController::class, 'voucher']);
    });
    Route::post('payment-vouchers', [OperationsController::class, 'createPaymentVoucher'])->middleware('permission:payments.create');
    Route::post('receipt-vouchers', [OperationsController::class, 'createReceiptVoucher'])->middleware('permission:payments.create');
    Route::post('payments', [OperationsController::class, 'pay'])->middleware('permission:payments.create');

    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('expenses', [ExpenseController::class, 'index']);
        Route::get('expenses/categories', [ExpenseController::class, 'categories']);
        Route::get('expenses/{expense}', [ExpenseController::class, 'show']);
        Route::get('expenses/{expense}/pdf', [PdfController::class, 'expense']);
    });
    Route::post('expenses', [ExpenseController::class, 'store'])->middleware('permission:expenses.create');
    Route::post('expenses/{expense}/cancel', [ExpenseController::class, 'cancel'])->middleware('permission:expenses.cancel');
    Route::middleware('permission:expenses.manage')->prefix('expense-categories')->group(function () {
        Route::post('/', [ExpenseController::class, 'storeCategory']);
        Route::put('{category}', [ExpenseController::class, 'updateCategory']);
        Route::delete('{category}', [ExpenseController::class, 'destroyCategory']);
    });

    Route::get('reports/{type}', [OperationsController::class, 'report'])
        ->whereIn('type', ['stock', 'stock-ledger', 'low-stock', 'purchases', 'sales', 'expenses', 'profit', 'financial'])
        ->middleware('permission:reports.view');
    Route::get('reports-consolidated/{type}', [OperationsController::class, 'consolidatedReport'])
        ->whereIn('type', ['stock', 'stock-ledger', 'low-stock', 'purchases', 'sales', 'expenses', 'profit', 'financial'])
        ->middleware('permission:reports.consolidated');

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
    Route::get('units-of-measurement', [UnitOfMeasurementController::class, 'index'])->middleware('permission:settings.manage|products.manage');
    Route::middleware('permission:settings.manage')->prefix('units-of-measurement')->group(function () {
        Route::post('/', [UnitOfMeasurementController::class, 'store']);
        Route::put('{unit}', [UnitOfMeasurementController::class, 'update']);
        Route::delete('{unit}', [UnitOfMeasurementController::class, 'destroy']);
    });
});
