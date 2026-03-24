<?php

use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\OfficeController as AdminOfficeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDashboardController;
use App\Http\Controllers\OfficeDashboardController;
use App\Http\Controllers\OrcamentoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\TransportadoraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth (public)
Route::post('/register', RegisterController::class);
Route::post('/login', LoginController::class);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', LogoutController::class);

    Route::get('/me', function (Request $request) {
        return response()->json($request->user()->load(['office.subscription.plan', 'companies', 'roles']));
    });

    // Companies (scoped by office)
    Route::apiResource('companies', CompanyController::class);
    Route::post('companies/{company}/certificado', [CompanyController::class, 'uploadCertificado']);

    // Dashboard
    Route::get('/dashboard/office', [OfficeDashboardController::class, 'index']);

    // Routes that require a company context (X-Company-Id header)
    Route::middleware(\App\Http\Middleware\ResolveTenant::class)->group(function () {
        // Dashboard per company
        Route::get('/dashboard/company', [CompanyDashboardController::class, 'index']);

        // Cadastros
        Route::apiResource('pessoas', PessoaController::class);
        Route::apiResource('produtos', ProdutoController::class);
        Route::apiResource('transportadoras', TransportadoraController::class);

        // Comercial
        Route::apiResource('orcamentos', OrcamentoController::class);
        Route::post('orcamentos/{orcamento}/converter', [OrcamentoController::class, 'convertToPedido']);
        Route::apiResource('pedidos', PedidoController::class);
    });

    // Admin routes (only admin role)
    Route::prefix('admin')->middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
        // Offices (contadores + diretas)
        Route::get('offices/map', [AdminOfficeController::class, 'map']);
        Route::post('offices/{office}/assign-plan', [AdminOfficeController::class, 'assignPlan']);
        Route::apiResource('offices', AdminOfficeController::class);

        // Invoices (cobranças)
        Route::get('invoices/dashboard', [AdminInvoiceController::class, 'dashboard']);
        Route::post('invoices/generate-monthly', [AdminInvoiceController::class, 'generateMonthly']);
        Route::apiResource('invoices', AdminInvoiceController::class);
    });
});
