<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
// Master Data
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\ProductCategoryController;
use App\Http\Controllers\Master\WarehouseController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ChartOfAccountController;
use App\Http\Controllers\Master\UserController;
// Purchase
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\ProcurementDemandController;
use App\Http\Controllers\Purchase\GoodsReceiptController;
use App\Http\Controllers\Purchase\PurchaseInvoiceController;
use App\Http\Controllers\Purchase\PurchaseReturnController;
use App\Http\Controllers\Purchase\PurchasePaymentController;
use App\Http\Controllers\Purchase\PurchaseReportController;
// Inventory
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Inventory\WarehouseTransferController;
use App\Http\Controllers\Inventory\StockOpnameController;
use App\Http\Controllers\Inventory\StockDispositionController;
use App\Http\Controllers\Inventory\InventoryReportController;
// Sales
use App\Http\Controllers\Sales\SalesOrderController;
use App\Http\Controllers\Sales\DeliveryController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\SalesReturnController;
use App\Http\Controllers\Sales\SalesPaymentController;
use App\Http\Controllers\Sales\SalesReportController;
// Accounting
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\ReportController;
// Approval
use App\Http\Controllers\ApprovalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // --- Dashboard ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- Profile (Breeze) ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // =========================================================
    // MASTER DATA
    // =========================================================
    Route::middleware('role:admin,purchasing,sales,gudang,finance')->prefix('master')->name('master.')->group(function () {
        Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::resource('products', ProductController::class);
        Route::patch('categories/{category}/toggle-status', [ProductCategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
        Route::resource('categories', ProductCategoryController::class);
        Route::patch('warehouses/{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus'])->name('warehouses.toggle-status');
        Route::resource('warehouses', WarehouseController::class);
        Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
        Route::resource('suppliers', SupplierController::class);
        Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
        Route::resource('customers', CustomerController::class);
    });

    Route::middleware('role:admin,finance')->prefix('master')->name('master.')->group(function () {
        Route::patch('chart-of-accounts/{chart_of_account}/toggle-status', [ChartOfAccountController::class, 'toggleStatus'])->name('chart-of-accounts.toggle-status');
        Route::resource('chart-of-accounts', ChartOfAccountController::class);
    });

    Route::middleware('role:admin')->prefix('master')->name('master.')->group(function () {
        Route::resource('users', UserController::class);
    });

    // =========================================================
    // PURCHASE MODULE
    // =========================================================
    Route::middleware('role:admin,purchasing')->prefix('purchase')->name('purchase.')->group(function () {
        Route::get('demands', [ProcurementDemandController::class, 'index'])->name('demands.index');
        Route::resource('orders', PurchaseOrderController::class)->parameters(['orders' => 'purchase_order']);
        Route::patch('orders/{purchase_order}/confirm', [PurchaseOrderController::class, 'confirm'])->name('orders.confirm');
        Route::patch('orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('orders.cancel');

        Route::resource('returns', PurchaseReturnController::class)->only(['index', 'create', 'store', 'show']);
        Route::patch('returns/{return}/send', [PurchaseReturnController::class, 'send'])->name('returns.send');
        Route::patch('returns/{return}/complete', [PurchaseReturnController::class, 'complete'])->name('returns.complete');
    });

    Route::middleware('role:admin,gudang')->prefix('purchase')->name('purchase.')->group(function () {
        Route::resource('goods-receipts', GoodsReceiptController::class)->only(['index', 'create', 'store', 'show']);
    });

    Route::middleware('role:admin,finance')->prefix('purchase')->name('purchase.')->group(function () {
        Route::resource('invoices', PurchaseInvoiceController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('payments', PurchasePaymentController::class)->only(['index', 'create', 'store', 'show']);
    });

    Route::middleware('role:admin,purchasing,finance')->prefix('purchase')->name('purchase.')->group(function () {
        Route::get('reports/fulfillment', [PurchaseReportController::class, 'fulfillment'])->name('reports.fulfillment');
        Route::get('reports/recap-by-product', [PurchaseReportController::class, 'recapByProduct'])->name('reports.recap-by-product');
    });

    // =========================================================
    // INVENTORY MODULE
    // =========================================================
    Route::middleware('role:admin,gudang')->prefix('inventory')->name('inventory.')->group(function () {
        Route::get('stock-card', [StockMovementController::class, 'stockCard'])->name('stock-card');
        Route::get('movements', [StockMovementController::class, 'index'])->name('movements.index');
        Route::resource('transfers', WarehouseTransferController::class)->only(['index', 'create', 'store', 'show']);
        Route::patch('transfers/{transfer}/ship', [WarehouseTransferController::class, 'ship'])->name('transfers.ship');
        Route::patch('transfers/{transfer}/receive', [WarehouseTransferController::class, 'receive'])->name('transfers.receive');
        Route::patch('transfers/{transfer}/cancel', [WarehouseTransferController::class, 'cancel'])->name('transfers.cancel');
        Route::resource('opname', StockOpnameController::class)->only(['index', 'create', 'store', 'show']);
        Route::patch('opname/{opname}/complete', [StockOpnameController::class, 'complete'])->name('opname.complete');
        Route::resource('dispositions', StockDispositionController::class)->only(['index', 'create', 'store']);
    });

    Route::middleware('role:admin,gudang,purchasing,sales,finance')->prefix('inventory')->name('inventory.')->group(function () {
        Route::get('stock-summary', [StockMovementController::class, 'summary'])->name('stock-summary');
        Route::get('reports/returns-by-product', [InventoryReportController::class, 'returnsByProduct'])->name('reports.returns-by-product');
    });

    // =========================================================
    // SALES MODULE
    // =========================================================
    Route::middleware('role:admin,sales')->prefix('sales')->name('sales.')->group(function () {
        Route::resource('orders', SalesOrderController::class)->parameters(['orders' => 'sales_order']);
        Route::patch('orders/{sales_order}/confirm', [SalesOrderController::class, 'confirm'])->name('orders.confirm');
        Route::patch('orders/{sales_order}/cancel', [SalesOrderController::class, 'cancel'])->name('orders.cancel');

        Route::resource('returns', SalesReturnController::class)->only(['index', 'create', 'store', 'show']);
        Route::patch('returns/{return}/receive', [SalesReturnController::class, 'receive'])->name('returns.receive');
        Route::patch('returns/{return}/complete', [SalesReturnController::class, 'complete'])->name('returns.complete');
    });

    Route::middleware('role:admin,gudang')->prefix('sales')->name('sales.')->group(function () {
        Route::resource('deliveries', DeliveryController::class)->only(['index', 'create', 'store', 'show']);
    });

    Route::middleware('role:admin,finance')->prefix('sales')->name('sales.')->group(function () {
        Route::patch('invoices/{invoice}/tax-invoice', [SalesInvoiceController::class, 'updateTaxInvoice'])->name('invoices.tax-invoice.update');
        Route::resource('invoices', SalesInvoiceController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('payments', SalesPaymentController::class)->only(['index', 'create', 'store', 'show']);
    });

    Route::middleware('role:admin,sales,finance')->prefix('sales')->name('sales.')->group(function () {
        Route::get('reports/fulfillment', [SalesReportController::class, 'fulfillment'])->name('reports.fulfillment');
        Route::get('reports/recap-by-product', [SalesReportController::class, 'recapByProduct'])->name('reports.recap-by-product');
    });

    // =========================================================
    // ACCOUNTING MODULE
    // =========================================================
    Route::middleware('role:admin,finance')->prefix('accounting')->name('accounting.')->group(function () {
        Route::resource('journals', JournalEntryController::class);
        Route::patch('journals/{journal}/post', [JournalEntryController::class, 'post'])->name('journals.post');

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('ledger', [ReportController::class, 'ledger'])->name('ledger');
            Route::get('trial-balance', [ReportController::class, 'trialBalance'])->name('trial-balance');
            Route::get('cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
            Route::get('receivables', [ReportController::class, 'receivables'])->name('receivables');
            Route::get('payables', [ReportController::class, 'payables'])->name('payables');
            Route::get('payables-by-vendor', [ReportController::class, 'payablesByVendor'])->name('payables-by-vendor');
            Route::get('receivables-by-customer', [ReportController::class, 'receivablesByCustomer'])->name('receivables-by-customer');
            Route::get('ledger-payable/{supplier}', [ReportController::class, 'ledgerPayable'])->name('ledger-payable');
            Route::get('ledger-receivable/{customer}', [ReportController::class, 'ledgerReceivable'])->name('ledger-receivable');
            Route::get('profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance-sheet');
            Route::get('stock-valuation', [ReportController::class, 'stockValuation'])->name('stock-valuation');
        });
    });

    // =========================================================
    // APPROVALS
    // =========================================================
    Route::middleware('role:admin,finance')->prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::patch('{approval}/approve', [ApprovalController::class, 'approve'])->name('approve');
        Route::patch('{approval}/reject', [ApprovalController::class, 'reject'])->name('reject');
    });

    // =========================================================
    // PDF EXPORT
    // =========================================================
    Route::middleware('role:admin,purchasing,sales,finance,gudang')->prefix('pdf')->name('pdf.')->group(function () {
        Route::get('purchase-order/{order}', [PurchaseOrderController::class, 'exportPdf'])->name('purchase-order');
        Route::get('purchase-invoice/{invoice}', [PurchaseInvoiceController::class, 'exportPdf'])->name('purchase-invoice');
        Route::get('delivery/{delivery}', [DeliveryController::class, 'exportPdf'])->name('delivery');
        Route::get('sales-order/{order}', [SalesOrderController::class, 'exportPdf'])->name('sales-order');
        Route::get('sales-invoice/{invoice}', [SalesInvoiceController::class, 'exportPdf'])->name('sales-invoice');
    });
});

require __DIR__.'/auth.php';
