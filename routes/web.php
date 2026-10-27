<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\FacilityInsurerController;
use App\Http\Controllers\Admin\MedicalCatalogController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuthorizationController;
use App\Http\Controllers\CoverageRosterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Facility\AuthorizationRequestController as FacilityRequestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Insurer\AuthorizationRequestController as InsurerRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Authorization is checked by the DocumentPolicy (same rules as the request).
    Route::get('/documents/{document}', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/authorizations/{authorization}', [AuthorizationController::class, 'show'])->name('authorizations.show');
    Route::get('/authorizations/{authorization}/pdf', [AuthorizationController::class, 'downloadPdf'])
        ->name('authorizations.pdf');
});

Route::middleware(['auth', 'active', 'role:facility_agent'])
    ->prefix('facility')
    ->name('facility.')
    ->group(function (): void {
        Route::get('/requests', [FacilityRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/create', [FacilityRequestController::class, 'create'])
            ->middleware('throttle:requests')
            ->name('requests.create');
        Route::post('/requests', [FacilityRequestController::class, 'store'])
            ->middleware('throttle:requests')
            ->name('requests.store');
        Route::get('/requests/{authorizationRequest}', [FacilityRequestController::class, 'show'])->name('requests.show');
        Route::get('/requests/{authorizationRequest}/edit', [FacilityRequestController::class, 'edit'])->name('requests.edit');
        Route::put('/requests/{authorizationRequest}', [FacilityRequestController::class, 'update'])->name('requests.update');
        Route::post('/requests/{authorizationRequest}/submit', [FacilityRequestController::class, 'submit'])->name('requests.submit');
        Route::post('/requests/{authorizationRequest}/cancel', [FacilityRequestController::class, 'cancel'])->name('requests.cancel');
        Route::post('/requests/{authorizationRequest}/documents', [DocumentController::class, 'store'])->name('documents.store');
    });

Route::middleware(['auth', 'active', 'role:insurer_manager'])
    ->prefix('insurer')
    ->name('insurer.')
    ->group(function (): void {
        Route::get('/requests', [InsurerRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{authorizationRequest}', [InsurerRequestController::class, 'show'])->name('requests.show');
        Route::post('/requests/{authorizationRequest}/approve', [InsurerRequestController::class, 'approve'])->name('requests.approve');
        Route::post('/requests/{authorizationRequest}/reject', [InsurerRequestController::class, 'reject'])->name('requests.reject');
        Route::post('/requests/{authorizationRequest}/request-information', [InsurerRequestController::class, 'requestInformation'])
            ->name('requests.request-information');
    });

Route::middleware(['auth', 'active', 'role:platform_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/facility-insurers', [FacilityInsurerController::class, 'index'])->name('facility-insurers.index');
        Route::put('/facility-insurers/{facility}', [FacilityInsurerController::class, 'update'])
            ->name('facility-insurers.update');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
        Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])
            ->name('organizations.update');
        Route::get('/medical-catalog', [MedicalCatalogController::class, 'index'])->name('medical-catalog.index');
        Route::post('/medical-catalog', [MedicalCatalogController::class, 'store'])->name('medical-catalog.store');
        Route::put('/medical-catalog/{medicalAct}', [MedicalCatalogController::class, 'update'])->name('medical-catalog.update');
        Route::post('/medical-catalog/{medicalAct}/tariffs', [MedicalCatalogController::class, 'storeTariff'])
            ->name('medical-catalog.tariffs.store');
    });

Route::middleware(['auth', 'active', 'can:manage-coverage-roster'])
    ->prefix('coverage-roster')
    ->name('coverage-roster.')
    ->group(function (): void {
        Route::get('/', [CoverageRosterController::class, 'index'])->name('index');
        Route::get('/template', [CoverageRosterController::class, 'template'])->name('template');
        Route::get('/create', [CoverageRosterController::class, 'create'])->name('create');
        Route::post('/', [CoverageRosterController::class, 'store'])->name('store');
        Route::get('/{beneficiary}/edit', [CoverageRosterController::class, 'edit'])->name('edit');
        Route::put('/{beneficiary}', [CoverageRosterController::class, 'update'])->name('update');
        Route::post('/import/preview', [CoverageRosterController::class, 'previewImport'])
            ->middleware('throttle:requests')
            ->name('import.preview');
        Route::post('/import', [CoverageRosterController::class, 'import'])
            ->middleware('throttle:requests')
            ->name('import');
    });
