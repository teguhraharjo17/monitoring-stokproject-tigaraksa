<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterItem\MasterItemController;
use App\Http\Controllers\MasterCompany\MasterCompanyController;
use App\Http\Controllers\MasterUser\MasterUserController;
use App\Http\Controllers\RekapData\RekapDataController;
use App\Http\Controllers\LevelStock\LevelStockController;
use App\Http\Controllers\MonitoringSubAssy\MonitoringSubAssyController;
use App\Http\Controllers\MonitoringMIP\MonitoringMIPController;
use App\Http\Controllers\MonitoringFinishGoods\MonitoringFinishGoodsController;

// ======================
// PUBLIC
// ======================
Route::get('/error', fn () => abort(500));

// ======================
// AUTH + VERIFIED
// ======================
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard.index'));
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Dashboard
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
    });

    // ================================
    // Admin-only route (register)
    // ================================
    Route::prefix('admin')->middleware('role:Admin')->name('admin.')->group(function () {
        Route::get('/register', [RegisteredUserController::class, 'create'])->name('make-account');
        Route::post('/register', [RegisteredUserController::class, 'store'])->name('register');
    });

    // ================================
    // MASTER DATA (Admin only)
    // ================================
    Route::prefix('master')->middleware('role:Admin')->name('master.')->group(function () {
        Route::prefix('item')->name('item.')->group(function () {
            Route::get('/', [MasterItemController::class, 'index'])->name('index');
            Route::get('/data', [MasterItemController::class, 'data'])->name('data');
            Route::post('/', [MasterItemController::class, 'store'])->name('store');
            Route::put('/{id}', [MasterItemController::class, 'update'])->name('update');
            Route::delete('/{id}', [MasterItemController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('company')->name('company.')->group(function () {
            Route::get('/', [MasterCompanyController::class, 'index'])->name('index');
            Route::get('/data', [MasterCompanyController::class, 'data'])->name('data');
            Route::post('/', [MasterCompanyController::class, 'store'])->name('store');
            Route::put('/{id}', [MasterCompanyController::class, 'update'])->name('update');
            Route::delete('/{id}', [MasterCompanyController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('user')->name('user.')->group(function () {
            Route::get('/', [MasterUserController::class, 'index'])->name('index');
            Route::get('/data', [MasterUserController::class, 'data'])->name('data');
            Route::post('/', [MasterUserController::class, 'store'])->name('store');
            Route::put('/{id}', [MasterUserController::class, 'update'])->name('update');
            Route::delete('/{id}', [MasterUserController::class, 'destroy'])->name('destroy');
        });
    });

    // ================================
    // DATA STOCK
    // ================================
    Route::prefix('datastock')->name('datastock.')->group(function () {

        // Rekap Data
        Route::prefix('rekap')->name('rekap.')->group(function () {
            Route::get('/', [RekapDataController::class, 'index'])->name('index');
            Route::get('/data', [RekapDataController::class, 'data'])->name('data');
            Route::post('/store', [RekapDataController::class, 'store'])->name('store');
            Route::get('/fetch', [RekapDataController::class, 'fetch'])->name('fetch');
        });

        // Level Stock
        Route::prefix('levelstock')->name('levelstock.')->group(function () {
            Route::get('/', [LevelStockController::class, 'index'])->name('index');
            Route::get('/data', [LevelStockController::class, 'data'])->name('data');
            Route::post('/update-jumlah-hari-kerja', [LevelStockController::class, 'updateJumlahHariKerja'])->name('updateHariKerja');
            Route::get('/hari-kerja', [LevelStockController::class, 'getJumlahHariKerja'])->name('getHariKerja');
            Route::post('/detail/store', [LevelStockController::class, 'storeDetail'])->name('detail.store');
            Route::get('/get-id', [LevelStockController::class, 'getLevelStokId'])->name('getId');
        });
    });

    // ================================
    // MONITORING
    // ================================
    Route::prefix('monitoring')->name('monitoring.')->group(function () {

        Route::prefix('subassy')->name('subassy.')->group(function () {
            Route::get('/', [MonitoringSubAssyController::class, 'index'])->name('index');
            Route::get('/data', [MonitoringSubAssyController::class, 'data'])->name('data');
            Route::post('/save', [MonitoringSubAssyController::class, 'save'])->name('save');
        });

        Route::prefix('mip')->name('mip.')->group(function () {
            Route::get('/', [MonitoringMIPController::class, 'index'])->name('index');
            Route::get('/data', [MonitoringMIPController::class, 'data'])->name('data');
            Route::post('/save', [MonitoringMIPController::class, 'save'])->name('save');
        });

        Route::prefix('finishgood')->name('finishgood.')->group(function () {
            Route::get('/', [MonitoringFinishGoodsController::class, 'index'])->name('index');
            Route::get('/data', [MonitoringFinishGoodsController::class, 'data'])->name('data');
            Route::post('/save', [MonitoringFinishGoodsController::class, 'save'])->name('save');
        });
    });
});

require __DIR__.'/auth.php';
