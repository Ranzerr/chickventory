<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('login', [AuthController::class, 'show'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products / ingredients — any authenticated user can add; only admins can edit/delete.
    Route::get('products', [ProductController::class, 'index'])->name('products');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::post('ingredients', [IngredientController::class, 'store'])->name('ingredients.store');

    Route::get('stock-in', [StockInController::class, 'index'])->name('stock-in');
    Route::post('stock-in', [StockInController::class, 'store'])->name('stock-in.store');
    Route::get('inventory-transactions', [InventoryTransactionController::class, 'index'])->name('inventory-transactions');

    // Suppliers — any authenticated user can add; only admins can edit/delete.
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');

    // Purchase orders — any authenticated user can create; approving/deleting is admin-only.
    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders');
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');

    // Purchases — any authenticated user can record a receipt; only admins can delete (reverses stock).
    Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases');
    Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');

    // Sales — any authenticated user can record a sale; only admins can delete (restores stock).
    Route::get('sales', [OrderItemController::class, 'index'])->name('sales');
    Route::post('sales', [OrderItemController::class, 'store'])->name('sales.store');

    // Expenses — any authenticated user can add; only admins can edit/delete/transfer.
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');

    // Recipe ingredients — any authenticated user can add; only admins can remove.
    Route::post('products/{product}/recipe', [RecipeController::class, 'store'])->name('recipes.store');

    Route::get('reports', [ReportController::class, 'index'])->name('reports');
    Route::get('users', [UserController::class, 'index'])->name('users');
    Route::get('settings', [SettingController::class, 'index'])->name('settings');

    // Admin-only: edit and delete access across the system's data.
    Route::middleware('admin')->group(function () {
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::put('ingredients/{ingredient}', [IngredientController::class, 'update'])->name('ingredients.update');
        Route::delete('ingredients/{ingredient}', [IngredientController::class, 'destroy'])->name('ingredients.destroy');
        Route::delete('products/{product}/recipe/{material}', [RecipeController::class, 'destroy'])->name('recipes.destroy');

        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');

        Route::delete('purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');

        Route::delete('sales/{order}', [OrderItemController::class, 'destroy'])->name('sales.destroy');

        Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::post('expenses/{expense}/transfer', [ExpenseController::class, 'transfer'])->name('expenses.transfer');

        Route::get('users/register', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
