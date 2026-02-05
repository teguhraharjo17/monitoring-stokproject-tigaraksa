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
use App\Http\Controllers\DataKanban\Reguler\RegulerController;
use App\Http\Controllers\DataKanban\AverageWeek\AverageWeekController;
use App\Http\Controllers\DataKanban\OSPERWEEK\OSPERWEEKController;
use App\Http\Controllers\SpkPacking\FormSpk\FormSpkController;
use App\Http\Controllers\SpkPacking\ApprovePPIC\ApprovePPICController;
use App\Http\Controllers\SpkPacking\ApproveMIP\ApproveMIPController;
use App\Http\Controllers\SpkPacking\ApproveFG\ApproveFGController;
use App\Http\Controllers\SpkPacking\ApprovePackingMember\ApprovePackingMemberController;
use App\Http\Controllers\SpkPacking\ApproveDiketahui\ApproveDiketahuiController;
use App\Http\Controllers\SpkPacking\SpkList\SpkListController;

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

    // Admin-only registration
    Route::prefix('admin')->middleware('role.access:Admin, PPIC')->name('admin.')->group(function () {
        Route::get('/register', [RegisteredUserController::class, 'create'])->name('make-account');
        Route::post('/register', [RegisteredUserController::class, 'store'])->name('register');
    });

    // ==========================
    // MASTER DATA (Admin, PPIC, Diketahui)
    // ==========================
    Route::prefix('master')->middleware('role.access:Admin,PPIC')->name('master.')->group(function () {
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

    // ==========================
    // DATA STOCK (Umum, tanpa role check)
    // ==========================
    Route::prefix('datastock')->name('datastock.')->group(function () {

        Route::prefix('rekap')->middleware('role.access:Admin,PPIC')->name('rekap.')->group(function () {
            Route::get('/', [RekapDataController::class, 'index'])->name('index');
            Route::get('/data', [RekapDataController::class, 'data'])->name('data');
            Route::post('/store', [RekapDataController::class, 'store'])->name('store');
            Route::get('/fetch', [RekapDataController::class, 'fetch'])->name('fetch');
        });


        Route::prefix('levelstock')->middleware('role.access:Admin,PPIC')->name('levelstock.')->group(function () {
            Route::get('/', [LevelStockController::class, 'index'])->name('index');
            Route::get('/data', [LevelStockController::class, 'data'])->name('data');
            Route::post('/update-jumlah-hari-kerja', [LevelStockController::class, 'updateJumlahHariKerja'])->name('updateHariKerja');
            Route::get('/hari-kerja', [LevelStockController::class, 'getJumlahHariKerja'])->name('getHariKerja');
            Route::post('/detail/store', [LevelStockController::class, 'storeDetail'])->name('detail.store');
            Route::get('/get-id', [LevelStockController::class, 'getLevelStokId'])->name('getId');
        });
    });

    // ==========================
    // MONITORING
    // ==========================
    Route::prefix('monitoring')->name('monitoring.')->group(function () {

        Route::prefix('subassy')->middleware('role.access:Sub Assy,PPIC')->name('subassy.')->group(function () {
            Route::get('/', [MonitoringSubAssyController::class, 'index'])->name('index');
            Route::post('/data', [MonitoringSubAssyController::class, 'data'])->name('data');
            Route::post('/save', [MonitoringSubAssyController::class, 'save'])->name('save');
            Route::get('/export', [MonitoringSubAssyController::class, 'export'])->name('export');
        });

        Route::prefix('mip')->middleware('role.access:MIP,PPIC')->name('mip.')->group(function () {
            Route::get('/', [MonitoringMIPController::class, 'index'])->name('index');
            Route::post('/data', [MonitoringMIPController::class, 'data'])->name('data');
            Route::post('/save', [MonitoringMIPController::class, 'save'])->name('save');
            Route::get('/export', [MonitoringMIPController::class, 'export'])->name('export');
        });

        Route::prefix('finishgood')->middleware('role.access:Finish Good,PPIC')->name('finishgood.')->group(function () {
            Route::get('/', [MonitoringFinishGoodsController::class, 'index'])->name('index');
            Route::post('/data', [MonitoringFinishGoodsController::class, 'data'])->name('data');
            Route::post('/save', [MonitoringFinishGoodsController::class, 'save'])->name('save');
            Route::get('/export', [MonitoringFinishGoodsController::class, 'export'])->name('export');
            Route::post('/update-stock-awal',[MonitoringFinishGoodsController::class, 'updateStockAwal'])->name('updateStockAwal');
        });
    });

    // ==========================
    // DATA KANBAN (umum)
    // ==========================
    Route::prefix('datakanban')->name('datakanban.')->group(function () {
        Route::prefix('reguler')->name('reguler.')->group(function () {
            Route::get('/', [RegulerController::class, 'index'])->name('index');
            Route::post('/data', [RegulerController::class, 'data'])->name('data');
            Route::get('/export', [RegulerController::class, 'export'])->name('export');
        });

        Route::prefix('averageweek')->name('averageweek.')->group(function () {
            Route::get('/', [AverageWeekController::class, 'index'])->name('index');
            Route::get('/data', [AverageWeekController::class, 'data'])->name('data');
            Route::get('/export', [AverageWeekController::class, 'export'])->name('export');
        });

        Route::prefix('osperweek')->name('osperweek.')->group(function () {
            Route::get('/', [OSPERWEEKController::class, 'index'])->name('index');
            Route::get('/data', [OSPERWEEKController::class, 'data'])->name('data');
            Route::get('/export', [OSPERWEEKController::class, 'export'])->name('export');
        });
    });

    // ==========================
    // SPK PACKING
    // ==========================
    Route::prefix('spkpacking')->name('spkpacking.')->group(function () {

        Route::prefix('formspk')->middleware('role.access:Finish Good,PPIC')->name('formspk.')->group(function () {
            Route::get('/', [FormSpkController::class, 'index'])->name('index');
            Route::get('/master-items', [FormSpkController::class, 'getMasterItems'])->name('masteritems');
            Route::get('/detail-info', [FormSpkController::class, 'getItemInfo'])->name('getiteminfo');
            Route::post('/store', [FormSpkController::class, 'store'])->name('store');
        });

        Route::prefix('approveppic')->middleware('role.access:PPIC')->name('approveppic.')->group(function () {
            Route::get('/', [ApprovePPICController::class, 'index'])->name('index');
            Route::get('/get-by-tanggal', [ApprovePPICController::class, 'getDataByTanggal'])->name('getbytanggal');
            Route::post('/update-detail/{id}', [ApprovePPICController::class, 'updateDetail'])->name('updatedetail');
            Route::post('/bulk-update', [ApprovePPICController::class, 'bulkUpdate'])->name('bulkupdate');
            Route::post('/approve', [ApprovePPICController::class, 'approve'])->name('approve');
        });

        Route::prefix('approvemip')->middleware('role.access:MIP')->name('approvemip.')->group(function () {
            Route::get('/', [ApproveMIPController::class, 'index'])->name('index');
            Route::get('/get-by-tanggal', [ApproveMIPController::class, 'getDataByTanggal'])->name('getbytanggal');
            Route::post('/bulk-update', [ApproveMIPController::class, 'bulkUpdate'])->name('bulkupdate');
            Route::post('/approve', [ApproveMIPController::class, 'approve'])->name('approve');
        });

        Route::prefix('approvefg')->middleware('role.access:Finish Good')->name('approvefg.')->group(function () {
            Route::get('/', [ApproveFGController::class, 'index'])->name('index');
            Route::get('/get-by-tanggal', [ApproveFGController::class, 'getDataByTanggal'])->name('getbytanggal');
            Route::post('/bulk-update', [ApproveFGController::class, 'bulkUpdate'])->name('bulkupdate');
            Route::post('/approve', [ApproveFGController::class, 'approve'])->name('approve');
        });

        Route::prefix('approvepacking')->middleware('role.access:Packing')->name('approvepacking.')->group(function () {
            Route::get('/', [ApprovePackingMemberController::class, 'index'])->name('index');
            Route::get('/get-by-tanggal', [ApprovePackingMemberController::class, 'getDataByTanggal'])->name('getbytanggal');
            Route::post('/bulk-update', [ApprovePackingMemberController::class, 'bulkUpdate'])->name('bulkupdate');
            Route::post('/approve', [ApprovePackingMemberController::class, 'approve'])->name('approve');
        });

        Route::prefix('approvediketahui')->middleware('role.access:Diketahui')->name('approvediketahui.')->group(function () {
            Route::get('/', [ApproveDiketahuiController::class, 'index'])->name('index');
            Route::get('/get-by-tanggal', [ApproveDiketahuiController::class, 'getDataByTanggal'])->name('getbytanggal');
            Route::post('/bulk-update', [ApproveDiketahuiController::class, 'bulkUpdate'])->name('bulkupdate');
            Route::post('/approve', [ApproveDiketahuiController::class, 'approve'])->name('approve');
        });

        Route::prefix('spklist')->name('spklist.')->group(function () {
            Route::get('/', [SpkListController::class, 'index'])->name('index');
            Route::get('/datatable', [SpkListController::class, 'datatable'])->name('datatable');
            Route::get('/export/{id}', [SpkListController::class, 'export'])->name('export');
        });
    });
});

require __DIR__.'/auth.php';