<?php

use App\Http\Controllers\BusinessRelationController;
use App\Http\Controllers\BusinessRelationSiteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoqController;
use App\Http\Controllers\BusinessEstateController;
use App\Http\Controllers\BusinessRelationContactController;
use App\Http\Controllers\CommercialBuildingController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\WorkOrderController;

// AUTH
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');



Route::prefix('business-relations')
    ->name('business-relations.')
    ->group(function () {

        Route::get('/', [BusinessRelationController::class, 'index'])
            ->name('index'); // ok - main page
        Route::get('/data', [BusinessRelationController::class, 'data'])
            ->name('data'); // ok - datatable ajax
        Route::get('/create', [BusinessRelationController::class, 'create'])
            ->name('create'); // ok - create page
        Route::get('/edit', [BusinessRelationController::class, 'edit'])
            ->name('edit'); // ok - edit page
        Route::get('/summary', [BusinessRelationController::class, 'summary'])
            ->name('summary'); //ok - summary page
        Route::post('/', [BusinessRelationController::class, 'store'])
            ->name('store'); // ok - store new
        Route::post('/edit-context', [BusinessRelationController::class, 'setEditContext'])
            ->name('set-edit-context');
        Route::get('/sites/{id}/detail', [BusinessRelationController::class, 'detail'])
            ->name('sites.detail'); // ok - site detail
        Route::get('/select2', [BusinessRelationController::class, 'select2'])
            ->name('select2'); // ok - select2 ajax
        Route::get('/sites/select2', [BusinessRelationSiteController::class, 'select2'])
            ->name('sites.select2');
        Route::get('/{id}/sites', [BusinessRelationSiteController::class, 'select2'])
            ->name('sites.select2') // ok - select2 ajax
            ->whereNumber('id');  // ok - select2 ajax
        Route::put('/{id}', [BusinessRelationController::class, 'update'])
            ->name('update'); // ok - update


        Route::get('/{id}', [BusinessRelationController::class, 'show'])
            ->name('show');
        Route::delete('/{id}', [BusinessRelationController::class, 'destroy'])
            ->name('destroy');
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

Route::prefix('/commercial-buildings')
    ->name('commercial-buildings.')
    ->group(function () {
        Route::get('/', [CommercialBuildingController::class, 'index'])
            ->name('index');
        Route::get('/data', [CommercialBuildingController::class, 'data'])
            ->name('data');
        Route::get('/{id}/detail', [CommercialBuildingController::class, 'detail'])
            ->name('detail')
            ->whereNumber('id');
        Route::get('/create', [CommercialBuildingController::class, 'create'])
            ->name('create');
        Route::post('/store', [CommercialBuildingController::class, 'store'])
            ->name('store');


        Route::post('/edit-context', [CommercialBuildingController::class, 'setEditContext'])
            ->name('set-edit-context');
        Route::get('/edit', [CommercialBuildingController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CommercialBuildingController::class, 'update'])->name('update');
    });

Route::prefix('/business-estates')
    ->name('business-estates.')
    ->group(function () {
        Route::get('/', [BusinessEstateController::class, 'index'])
            ->name('index');
        Route::get('/data', [BusinessEstateController::class, 'data'])
            ->name('data');
        Route::get('/{id}/detail', [BusinessEstateController::class, 'detail'])
            ->name('detail')
            ->whereNumber('id');
        Route::get('/create', [BusinessEstateController::class, 'create'])
            ->name('create');

        Route::post('/store', [BusinessEstateController::class, 'store'])
            ->name('store');
        Route::post('/edit-context', [BusinessEstateController::class, 'setEditContext'])
            ->name('set-edit-context');

        // 👉 EDIT
        Route::get('/edit', [BusinessEstateController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BusinessEstateController::class, 'update'])->name('update');
    });




Route::prefix('sales-orders')->name('sales-orders.')->group(function () {

    Route::get('/', [SalesOrderController::class, 'index'])->name('index');
    Route::get('/data', [SalesOrderController::class, 'data'])->name('data');

    Route::get('/create', [SalesOrderController::class, 'create'])->name('create');
    Route::post('/', [SalesOrderController::class, 'store'])->name('store');

    Route::get('/select2', [SalesOrderController::class, 'select2'])
        ->name('select2');

    Route::get('/{id}', [SalesOrderController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [SalesOrderController::class, 'edit'])->name('edit');
    Route::put('/{id}', [SalesOrderController::class, 'update'])->name('update');
    Route::delete('/{id}', [SalesOrderController::class, 'destroy'])->name('destroy');
});

Route::prefix('work-orders')->name('work-orders.')->group(function () {

    Route::get('/', [WorkOrderController::class, 'index'])->name('index');
    Route::get('/data', [WorkOrderController::class, 'data'])->name('data');

    Route::get('/create', [WorkOrderController::class, 'create'])->name('create');
    Route::post('/', [WorkOrderController::class, 'store'])->name('store');

    Route::get('/{id}', [WorkOrderController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [WorkOrderController::class, 'edit'])->name('edit');
    Route::put('/{id}', [WorkOrderController::class, 'update'])->name('update');
    Route::delete('/{id}', [WorkOrderController::class, 'destroy'])->name('destroy');
});

Route::prefix('boq')->name('boq.')->group(function () {

    Route::get('/', [BoqController::class, 'index'])->name('index');
    Route::get('/data', [BoqController::class, 'data'])->name('data');

    Route::get('/create', [BoqController::class, 'create'])->name('create');
    Route::post('/', [BoqController::class, 'store'])->name('store');

    Route::get('/{id}', [BoqController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [BoqController::class, 'edit'])->name('edit');
    Route::put('/{id}', [BoqController::class, 'update'])->name('update');
    Route::delete('/{id}', [BoqController::class, 'destroy'])->name('destroy');
});


Route::prefix('/api')
    ->name('api.')
    ->group(function () {
        Route::get('/get-data-site', [BusinessRelationSiteController::class, 'getDataSite'])
            ->name('get-data-site'); // ok - select2 ajax

        Route::get('/get-contact-site/{id}', [BusinessRelationContactController::class, 'getDataContactSite'])
            ->name('get-data-contact-site'); // ok - select2 ajax

        Route::get('/get-data-br', [BusinessRelationController::class, 'getDataBR'])
            ->name('get-data-br');
    });




Route::get('/', function () {
    return redirect('/business-relations');
});
