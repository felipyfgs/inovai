<?php

use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\OfficeController as AdminOfficeController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDashboardController;
use App\Http\Controllers\OfficeDashboardController;
use App\Http\Controllers\OrcamentoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\TransportadoraController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\ResolveTenant;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth (public)
Route::post('/register', RegisterController::class);
Route::post('/login', LoginController::class);
Route::post('/forgot-password', [ForgotPasswordController::class, '__invoke']);
Route::post('/reset-password', [ResetPasswordController::class, '__invoke']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', LogoutController::class);

    Route::get('/me', function (Request $request) {
        return response()->json($request->user()->load(['office.subscription.plan', 'companies', 'roles']));
    });
    Route::put('/me', [UserController::class, 'updateProfile']);
    Route::put('/me/password', [UserController::class, 'updatePassword']);

    Route::get('/default-company', function (Request $request) {
        $user = $request->user();

        // Admin: no default company
        if ($user->hasRole('admin')) {
            return response()->json(['company' => null]);
        }

        // Contador (office_user/accountant): first company of their office
        if ($user->hasAnyRole(['office_user', 'accountant'])) {
            $company = Company::where('office_id', $user->office_id)
                ->orderBy('id')
                ->first();

            return response()->json(['company' => $company]);
        }

        // Empresário (company_user): first attached company
        $company = $user->companies()->orderBy('companies.id')->first();

        return response()->json(['company' => $company]);
    });

    // Office-scoped routes (require active subscription)
    Route::middleware(EnsureActiveSubscription::class)->group(function () {
        // Users management
        Route::apiResource('users', UserController::class);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::get('users/{user}/companies', [UserController::class, 'companies']);
        Route::post('users/{user}/companies', [UserController::class, 'attachCompanies']);
        Route::delete('users/{user}/companies/{company}', [UserController::class, 'detachCompany']);

        // Companies (scoped by office)
        Route::apiResource('companies', CompanyController::class);
        Route::post('companies/{company}/certificado', [CompanyController::class, 'uploadCertificado']);
        Route::get('companies/{company}/users', [CompanyController::class, 'users']);
        Route::post('companies/{company}/users', [CompanyController::class, 'attachUser']);
        Route::delete('companies/{company}/users/{user}', [CompanyController::class, 'detachUser']);

        // Dashboard
        Route::get('/dashboard/office', [OfficeDashboardController::class, 'index']);

        // Routes that require a company context (X-Company-Id header)
        Route::middleware(ResolveTenant::class)->group(function () {
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
    });

    // Admin routes (only admin role)
    Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
        // Admin users
        Route::apiResource('admins', AdminUserController::class);

        // Plans
        Route::apiResource('plans', PlanController::class);

        // Offices (contadores + diretas)
        Route::get('offices/map', [AdminOfficeController::class, 'map']);
        Route::post('offices/{office}/assign-plan', [AdminOfficeController::class, 'assignPlan']);
        Route::delete('offices/{office}/plan', [AdminOfficeController::class, 'removePlan']);
        Route::apiResource('offices', AdminOfficeController::class);

        // Invoices (cobranças)
        Route::get('invoices/dashboard', [AdminInvoiceController::class, 'dashboard']);
        Route::get('invoices/chart', [AdminInvoiceController::class, 'chart']);
        Route::get('invoices/plans-chart', [AdminInvoiceController::class, 'plansChart']);
        Route::get('invoices/status-chart', [AdminInvoiceController::class, 'statusChart']);
        Route::get('invoices/overdue-chart', [AdminInvoiceController::class, 'overdueChart']);
        Route::post('invoices/generate-monthly', [AdminInvoiceController::class, 'generateMonthly']);
        Route::apiResource('invoices', AdminInvoiceController::class);

        // Offices charts
        Route::get('offices/growth-chart', [AdminOfficeController::class, 'growthChart']);
    });
});
