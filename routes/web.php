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
use App\Http\Controllers\TestingItemController;
use App\Http\Controllers\WorkOrderController;

use App\Http\Controllers\TestingUnitController;
use App\Http\Controllers\TestingParameterController;
use App\Http\Controllers\TestingKelompokMatriksSampleController;
use App\Http\Controllers\TestingStandardController;
use App\Http\Controllers\TestingMatriksSampleController;
use App\Http\Controllers\TestingPointController;

// AUTH
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


//TestingUnits
Route::prefix('testing-units')->name('testing-units.')->group(function () {
    Route::get('/select2byid', [TestingUnitController::class, 'select2byid'])
        ->name('select2byid');
    Route::get('/select2', [TestingUnitController::class, 'select2'])->name('select2');
    Route::get('/', [TestingUnitController::class, 'index'])->name('index');
    Route::get('/data', [TestingUnitController::class, 'data'])->name('data');
    Route::get('/create', [TestingUnitController::class, 'create'])->name('create');
    Route::post('/', [TestingUnitController::class, 'store'])->name('store');
    Route::get('/{id}', [TestingUnitController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [TestingUnitController::class, 'edit'])->name('edit');
    Route::put('/{id}', [TestingUnitController::class, 'update'])->name('update');
    Route::delete('/{id}', [TestingUnitController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/detail', [TestingUnitController::class, 'detail'])
        ->name('detail');
    Route::get('/{id}/history', [TestingUnitController::class, 'history'])
        ->name('history')
        ->whereNumber('id');
});

//testing nana

Route::prefix('testing-parameters')->name('testing-parameters.')->group(function () {


    Route::post(
        '/testing-parameters/delete-attachment',
        [TestingParameterController::class, 'deleteAttachment']
    )->name('delete-attachment');

    Route::get('/', [TestingParameterController::class, 'index'])->name('index');
    Route::get('/data', [TestingParameterController::class, 'data'])->name('data');
    Route::get('/select2', [TestingParameterController::class, 'select2'])
        ->name('select2');
    Route::get('/select2byid', [TestingParameterController::class, 'select2byid'])
        ->name('select2byid');

    Route::get('/create', [TestingParameterController::class, 'create'])->name('create');
    Route::post('/', [TestingParameterController::class, 'store'])->name('store');


    Route::get('/{id}', [TestingParameterController::class, 'show'])->name('show')->whereNumber('id');
    Route::get('/{id}/edit', [TestingParameterController::class, 'edit'])->name('edit')->whereNumber('id');
    Route::get('/{id}/history', [TestingParameterController::class, 'history'])
        ->name('history')
        ->whereNumber('id');
    Route::put('/{id}', [TestingParameterController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/{id}', [TestingParameterController::class, 'destroy'])->name('destroy')->whereNumber('id');
});



Route::prefix('testing-kelompok-matriks-samples')
    ->name('testing-kelompok-matriks-samples.')
    ->group(function () {

        Route::get('/', [TestingKelompokMatriksSampleController::class, 'index'])->name('index');
        Route::get('/data', [TestingKelompokMatriksSampleController::class, 'data'])->name('data');

        Route::get('/create', [TestingKelompokMatriksSampleController::class, 'create'])->name('create');
        Route::post('/', [TestingKelompokMatriksSampleController::class, 'store'])->name('store');

        Route::get('/select2', [TestingKelompokMatriksSampleController::class, 'select2'])->name('select2');

        Route::get('/{id}', [TestingKelompokMatriksSampleController::class, 'show'])->name('show')->whereNumber('id');
        Route::put('/{id}', [TestingKelompokMatriksSampleController::class, 'update'])->name('update')->whereNumber('id');
        Route::delete('/{id}', [TestingKelompokMatriksSampleController::class, 'destroy'])->name('destroy')->whereNumber('id');
        Route::get(
            '/{id}/detail',
            [TestingKelompokMatriksSampleController::class, 'detail']
        )->name('detail')->whereNumber('id');
    });


Route::prefix('testing-standards')
    ->name('testing-standards.')
    ->group(function () {

        Route::get('/', [TestingStandardController::class, 'index'])->name('index');
        Route::get('/data', [TestingStandardController::class, 'data'])->name('data');

        Route::post(
            '/delete-attachment',
            [TestingStandardController::class, 'deleteAttachment']
        )->name('delete-attachment');


        Route::get('/create', [TestingStandardController::class, 'create'])->name('create');
        Route::get('/{id}', [TestingStandardController::class, 'show'])->name('show')->whereNumber('id');


        Route::put('/{id}', [TestingStandardController::class, 'update'])->name('update');
        Route::post('/', [TestingStandardController::class, 'store'])->name('store');
        Route::get('/select2', [TestingStandardController::class, 'select2'])->name('select2');

        Route::get('/{id}/detail', [TestingStandardController::class, 'detail'])->name('detail');
        Route::delete('/{id}', [TestingStandardController::class, 'destroy'])->name('destroy');
    });


Route::prefix('testing-points')
    ->name('testing-points.')
    ->group(function () {
        Route::post(
            '/delete-attachment',
            [TestingPointController::class, 'deleteAttachment']
        )->name('delete-attachment');
        Route::put('/{id}', [TestingPointController::class, 'update'])->name('update');
        Route::post('/', [TestingPointController::class, 'store'])->name('store');

        Route::get('/', [TestingPointController::class, 'index'])->name('index');
        Route::get('/data', [TestingPointController::class, 'data'])->name('data');



        Route::get('/create', [TestingPointController::class, 'create'])->name('create');
        Route::get('/select2', [TestingPointController::class, 'select2'])->name('select2');
        Route::get('/{id}', [TestingPointController::class, 'detail'])->name('detail');
        Route::get('/{id}/detail', [TestingPointController::class, 'detail'])->name('detail');
        Route::delete('/{id}', [TestingPointController::class, 'destroy'])->name('destroy');
    });



Route::prefix('testing-matriks-samples')
    ->name('testing-matriks-samples.')
    ->group(function () {

        Route::get('/', [TestingMatriksSampleController::class, 'index'])->name('index');
        Route::get('/data', [TestingMatriksSampleController::class, 'data'])->name('data');

        Route::get('/create', [TestingMatriksSampleController::class, 'create'])->name('create');
        Route::post('/', [TestingMatriksSampleController::class, 'store'])->name('store');
        Route::get('/select2', [TestingMatriksSampleController::class, 'select2'])->name('select2');

        Route::get('/{id}', [TestingMatriksSampleController::class, 'detail'])->name('detail');
        Route::get('/{id}/detail', [TestingMatriksSampleController::class, 'detail'])->name('detail');
        Route::put('/{id}', [TestingMatriksSampleController::class, 'update'])->name('update');
        Route::delete('/{id}', [TestingMatriksSampleController::class, 'destroy'])->name('destroy');
    });


Route::prefix('testing-items')
    ->name('testing-items.')
    ->group(function () {

        Route::get('/by-point/{id}', [TestingItemController::class, 'byPoint'])
            ->name('testing-items.byPoint');
        Route::get('/', [TestingItemController::class, 'index'])->name('index');
        Route::get('/data', [TestingItemController::class, 'data'])->name('data');

        Route::get('/create', [TestingItemController::class, 'create'])->name('create');
        Route::post('/', [TestingItemController::class, 'store'])->name('store');

        Route::put('/{id}', [TestingItemController::class, 'update'])->name('update');
        Route::delete('/{id}', [TestingItemController::class, 'destroy'])->name('destroy');
    });


Route::prefix('business-relations')
    ->name('business-relations.')
    ->group(function () {

        Route::get('/', [BusinessRelationController::class, 'index'])
            ->name('index'); // ok - main page

        Route::get('/data', [BusinessRelationController::class, 'data'])
            ->name('data'); // ok - datatable ajax

        Route::get('/select2', [BusinessRelationController::class, 'select2'])
            ->name('select2'); // ok - select2 ajax


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



        Route::get('/sites/select2', [BusinessRelationSiteController::class, 'select2'])
            ->name('sites.select2');
        Route::get('/{id}/sites', [BusinessRelationSiteController::class, 'select2'])
            ->name('sites.select2') // ok - select2 ajax
            ->whereNumber('id');  // ok - select2 ajax

        Route::get('/{id}', [BusinessRelationController::class, 'detail'])->name('detail');

        Route::put('/{id}', [BusinessRelationController::class, 'update'])
            ->name('update'); // ok - update

        // Route::get('/{id}', [BusinessRelationController::class, 'show'])
        //     ->name('show');
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

        Route::get('/select2', [BusinessRelationSiteController::class, 'select2'])->name('select2');


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

        Route::get('/select2', [CommercialBuildingController::class, 'select2'])
            ->name('select2');

        Route::get('/{id}', [CommercialBuildingController::class, 'show'])->name('show')->whereNumber('id');

        // Route::get('/{id}', [CommercialBuildingController::class, 'detail'])
        //     ->name('detail')
        //     ->whereNumber('id');
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

        Route::get('/create', [BusinessEstateController::class, 'create'])
            ->name('create');
        Route::get('/select2', [BusinessEstateController::class, 'select2'])
            ->name('select2');
        Route::get('/{id}', [BusinessEstateController::class, 'show'])->name('show');


        Route::get('/{id}/detail', [BusinessEstateController::class, 'detail'])
            ->name('detail')
            ->whereNumber('id');



        Route::post('/store', [BusinessEstateController::class, 'store'])
            ->name('store');
        Route::post('/edit-context', [BusinessEstateController::class, 'setEditContext'])
            ->name('set-edit-context');

        // 👉 EDIT
        Route::get('/edit', [BusinessEstateController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BusinessEstateController::class, 'update'])->name('update');
    });


Route::prefix('/business-relation-contacts')
    ->name('business-relation-contacts.')
    ->group(function () {

        Route::get('/', [BusinessRelationContactController::class, 'index'])
            ->name('index');

        Route::get('/data', [BusinessRelationContactController::class, 'data'])
            ->name('data');

        Route::get('/create', [BusinessRelationContactController::class, 'create'])
            ->name('create');

        Route::get('/select2', [BusinessRelationContactController::class, 'select2'])->name('select2');


        Route::get('/{id}', [BusinessRelationContactController::class, 'show'])->name('show');


        Route::get('/{id}/detail', [BusinessRelationContactController::class, 'detail'])
            ->name('detail')
            ->whereNumber('id');



        Route::post('/store', [BusinessRelationContactController::class, 'store'])
            ->name('store');
        Route::post('/edit-context', [BusinessRelationContactController::class, 'setEditContext'])
            ->name('set-edit-context');

        // 👉 EDIT
        Route::get('/edit', [BusinessRelationContactController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BusinessRelationContactController::class, 'update'])->name('update');
    });





Route::prefix('sales-orders')->name('sales-orders.')->group(function () {

    Route::get('/', [SalesOrderController::class, 'index'])->name('index');
    Route::get('/data', [SalesOrderController::class, 'data'])->name('data');

    Route::get('/create', [SalesOrderController::class, 'create'])->name('create');
    Route::post('/', [SalesOrderController::class, 'store'])->name('store');

    Route::get('/select2', [SalesOrderController::class, 'select2'])
        ->name('select2');

    Route::get('/{id}/detail', [SalesOrderController::class, 'detail'])
        ->name('detail')
        ->whereNumber('id');


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

    Route::get('/select2', [WorkOrderController::class, 'select2'])
        ->name('select2');

    Route::get('/{id}/detail', [WorkOrderController::class, 'detail'])
        ->name('detail')
        ->whereNumber('id');

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

    Route::get('/select2', [BoqController::class, 'select2'])
        ->name('select2');

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

use App\Http\Controllers\DashboardController;

Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/',             [DashboardController::class, 'index'])->name('index');
    Route::get('/summary',      [DashboardController::class, 'summary'])->name('summary');
    Route::get('/so-per-month', [DashboardController::class, 'soPerMonth'])->name('soPerMonth');
});

// Update redirect root ke dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});
