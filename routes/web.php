<?php

use App\Http\Controllers\BusinessRelationController;
use App\Http\Controllers\BusinessRelationSiteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// AUTH
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');



Route::prefix('business-relations')
    ->name('business-relations.')
    ->group(function () {

        // ============================
        // PAGE: LIST / INDEX
        // ============================
        Route::get('/', [BusinessRelationController::class, 'index'])
            ->name('index');
        // Menampilkan halaman utama BR (DataTable)

        // ============================
        // DATA: DATATABLE AJAX
        // ============================
        Route::get('/data', [BusinessRelationController::class, 'data'])
            ->name('data');
        // Endpoint AJAX untuk DataTable BR

        // ============================
        // PAGE: CREATE
        // ============================
        Route::get('/create', [BusinessRelationController::class, 'create'])
            ->name('create');
        // Halaman form create BR (+ BRS)

        // ============================
        // ACTION: STORE (AJAX)
        // ============================
        Route::post('/', [BusinessRelationController::class, 'store'])
            ->name('store');
        // Simpan BR + BRS (via AJAX)

        // ============================
        // DATA: SELECT2 BR
        // ============================
        Route::get('/select2', [BusinessRelationController::class, 'select2'])
            ->name('select2');
        // Endpoint Select2 untuk Business Relation

        // ============================
        // DATA: SELECT2 BRS (BY BR)
        // ============================
        Route::get('/{id}/sites', [BusinessRelationSiteController::class, 'select2'])
            ->name('sites.select2')
            ->whereNumber('id');
        // Endpoint Select2 Site berdasarkan BR
        // HARUS di atas route '/{id}' agar tidak bentrok

        // ============================
        // PAGE / DATA: SHOW
        // ============================
        Route::get('/{id}', [BusinessRelationController::class, 'show'])
            ->name('show');
        // Ambil detail BR (edit modal / view detail)

        // ============================
        // ACTION: UPDATE
        // ============================
        Route::put('/{id}', [BusinessRelationController::class, 'update'])
            ->name('update');
        // Update data BR (AJAX)

        // ============================
        // ACTION: DELETE
        // ============================
        Route::delete('/{id}', [BusinessRelationController::class, 'destroy'])
            ->name('destroy');
        // Soft/Hard delete BR (AJAX + confirm)
    });

Route::prefix('business-relation-sites')
    ->name('business-relation-sites.')
    ->group(function () {

        Route::get('/', [BusinessRelationSiteController::class, 'index'])
            ->name('index');        // halaman list BRS

        Route::get('/data', [BusinessRelationSiteController::class, 'data'])
            ->name('data');         // DataTable AJAX

        Route::get('/{id}', [BusinessRelationSiteController::class, 'show'])
            ->name('show')
            ->whereNumber('id');    // detail (modal)

        Route::put('/{id}', [BusinessRelationSiteController::class, 'update'])
            ->name('update')
            ->whereNumber('id');    // update

        Route::delete('/{id}', [BusinessRelationSiteController::class, 'destroy'])
            ->name('destroy')
            ->whereNumber('id');    // delete
    });



Route::get('/', function () {
    return view('welcome');
});
