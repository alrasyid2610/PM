<?php

use App\Http\Controllers\BusinessRelationController;
use App\Http\Controllers\BusinessRelationSiteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoqController;
use App\Http\Controllers\FieldworkBoqController;
use App\Http\Controllers\FieldworkController;
use App\Http\Controllers\BusinessEstateController;
use App\Http\Controllers\BusinessRelationContactController;
use App\Http\Controllers\CommercialBuildingController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\TestingItemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\TestingUnitController;
use App\Http\Controllers\TestingParameterController;
use App\Http\Controllers\TestingKelompokMatriksSampleController;
use App\Http\Controllers\TestingStandardController;
use App\Http\Controllers\TestingMatriksSampleController;
use App\Http\Controllers\TestingPointController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\TerminController;
use App\Http\Controllers\WoPeriodController;
use Spatie\LaravelPdf\Facades\Pdf;

Route::get('/test-so-pdf', function () {
    // return view('pdf.sales_order');
    return Pdf::view('pdf.sales_order')
        ->withBrowsershot(function ($browsershot) {
            $browsershot->setChromePath('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe');
            $browsershot->timeout(120);
        })
        ->format('a4')
        ->name('SO-25-001.pdf');
});




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
        Route::get('/{id}/history', [TestingKelompokMatriksSampleController::class, 'history'])->name('history')->whereNumber('id');
    });

Route::prefix('contracts')
    ->name('contracts.')
    ->group(function () {

        Route::get('/select2byid', [ContractController::class, 'select2byid'])->name('select2byid');
        Route::get('/select2',     [ContractController::class, 'select2'])->name('select2');

        Route::get('/',            [ContractController::class, 'index'])->name('index');
        Route::get('/data',        [ContractController::class, 'data'])->name('data');
        Route::get('/create',      [ContractController::class, 'create'])->name('create');
        Route::post('/',           [ContractController::class, 'store'])->name('store');
        Route::post('/delete-attachment', [ContractController::class, 'deleteAttachment'])->name('delete-attachment');
        Route::get('/{id}',        [ContractController::class, 'show'])->name('show');
        Route::put('/{id}',        [ContractController::class, 'update'])->name('update');
        Route::post('/{id}',       [ContractController::class, 'update'])->name('update.post');
        Route::delete('/{id}',     [ContractController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/detail', [ContractController::class, 'detail'])->name('detail');
        Route::get('/{id}/history', [ContractController::class, 'history'])->name('history')->whereNumber('id');
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
        Route::get('/{id}/history', [TestingStandardController::class, 'history'])->name('history')->whereNumber('id');
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
        Route::get('/{id}/history', [TestingPointController::class, 'history'])->name('history')->whereNumber('id');
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
        Route::get('/{id}/history', [TestingMatriksSampleController::class, 'history'])->name('history')->whereNumber('id');
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
        Route::get('/{id}/history', [BusinessRelationController::class, 'history'])->name('history')->whereNumber('id');

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
        Route::get('/{id}/history', [CommercialBuildingController::class, 'history'])->name('history')->whereNumber('id');
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
        Route::get('/{id}/history', [BusinessEstateController::class, 'history'])
            ->name('history')
            ->whereNumber('id');
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
        Route::get('/{id}/history', [BusinessRelationContactController::class, 'history'])->name('history')->whereNumber('id');
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

    Route::get('/{id}/wo-progress', [SalesOrderController::class, 'woProgress'])
        ->name('wo-progress')
        ->whereNumber('id');

    Route::get('/{id}', [SalesOrderController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [SalesOrderController::class, 'edit'])->name('edit');
    Route::put('/{id}', [SalesOrderController::class, 'update'])->name('update');
    Route::delete('/{id}', [SalesOrderController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/history', [SalesOrderController::class, 'history'])->name('history')->whereNumber('id');
});

Route::prefix('work-orders')->name('work-orders.')->group(function () {

    Route::get('/', [WorkOrderController::class, 'index'])->name('index');
    Route::get('/data', [WorkOrderController::class, 'data'])->name('data');

    Route::get('/create', [WorkOrderController::class, 'create'])->name('create');
    Route::post('/', [WorkOrderController::class, 'store'])->name('store');

    Route::get('/by-so/{id}', [WorkOrderController::class, 'bySo'])->name('by-so');

    Route::get('/select2', [WorkOrderController::class, 'select2'])
        ->name('select2');

    Route::get('/{id}/detail', [WorkOrderController::class, 'detail'])
        ->name('detail')
        ->whereNumber('id');

    Route::get('/{id}', [WorkOrderController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [WorkOrderController::class, 'edit'])->name('edit');
    Route::put('/{id}', [WorkOrderController::class, 'update'])->name('update');
    Route::put('/{id}/period', [WorkOrderController::class, 'assignPeriod'])->name('assign-period')->whereNumber('id');
    Route::delete('/{id}', [WorkOrderController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/history', [WorkOrderController::class, 'history'])->name('history')->whereNumber('id');
    Route::get('/{id}/boq-progress', [WorkOrderController::class, 'boqProgress'])->name('boq-progress')->whereNumber('id');
    Route::post('/{id}/duplicate', [WorkOrderController::class, 'duplicate'])->name('duplicate')->whereNumber('id');
});

Route::prefix('wo-periods')->name('wo-periods.')->group(function () {
    Route::get('/by-so/{id_so}', [WoPeriodController::class, 'bySo'])->name('by-so');
    Route::get('/select2', [WoPeriodController::class, 'select2'])->name('select2');
    Route::post('/', [WoPeriodController::class, 'store'])->name('store');
    Route::put('/{id}', [WoPeriodController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/{id}', [WoPeriodController::class, 'destroy'])->name('destroy')->whereNumber('id');
});

Route::prefix('fieldwork-boq')->name('fieldwork-boq.')->group(function () {
    Route::get('/by-fwo/{id_fwo}', [FieldworkBoqController::class, 'byFwo'])->name('by-fwo');
    Route::put('/{id_fwo}',        [FieldworkBoqController::class, 'update'])->name('update')->whereNumber('id_fwo');
});

Route::prefix('fieldworks')->name('fieldworks.')->group(function () {
    Route::get('/',             [FieldworkController::class, 'index'])->name('index');
    Route::get('/data',         [FieldworkController::class, 'data'])->name('data');
    Route::get('/create',       [FieldworkController::class, 'create'])->name('create');
    Route::post('/',            [FieldworkController::class, 'store'])->name('store');
    Route::get('/{id}',         [FieldworkController::class, 'detail'])->name('detail')->whereNumber('id');
    Route::put('/{id}',         [FieldworkController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/{id}',      [FieldworkController::class, 'destroy'])->name('destroy')->whereNumber('id');
    Route::get('/{id}/history',   [FieldworkController::class, 'history'])->name('history')->whereNumber('id');
    Route::put('/{id}/personels',  [FieldworkController::class, 'updatePersonels'])->name('updatePersonels')->whereNumber('id');
    Route::post('/{id}/duplicate', [FieldworkController::class, 'duplicate'])->name('duplicate')->whereNumber('id');
});

Route::prefix('termin')->name('termin.')->group(function () {
    Route::get('/',                   [TerminController::class, 'index'])->name('index');
    Route::get('/data',               [TerminController::class, 'data'])->name('data');
    Route::get('/create',             [TerminController::class, 'create'])->name('create');
    Route::post('/',                  [TerminController::class, 'store'])->name('store');
    Route::get('/select2',            [TerminController::class, 'select2'])->name('select2');
    Route::post('/delete-attachment', [TerminController::class, 'deleteAttachment'])->name('delete-attachment');
    Route::get('/{id}',               [TerminController::class, 'show'])->name('show')->whereNumber('id');
    Route::get('/{id}/detail',        [TerminController::class, 'detail'])->name('detail')->whereNumber('id');
    Route::get('/{id}/history',       [TerminController::class, 'history'])->name('history')->whereNumber('id');
    Route::put('/{id}',               [TerminController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/{id}',            [TerminController::class, 'destroy'])->name('destroy')->whereNumber('id');
});

Route::prefix('boq')->name('boq.')->group(function () {

    Route::get('/', [BoqController::class, 'index'])->name('index');
    Route::get('/data', [BoqController::class, 'data'])->name('data');

    Route::get('/create', [BoqController::class, 'create'])->name('create');
    Route::post('/', [BoqController::class, 'store'])->name('store');

    Route::get('/select2', [BoqController::class, 'select2'])
        ->name('select2');

    Route::get('/by-wo/{id}', [BoqController::class, 'byWo'])->name('by-wo');
    Route::get('/select2-by-wo/{id_wo}', [BoqController::class, 'select2ByWo'])->name('select2-by-wo');
    Route::get('/{id}/section-items', [BoqController::class, 'sectionItems'])->name('section-items');

    Route::get('/{id}', [BoqController::class, 'show'])->name('show');
    Route::put('/{id}', [BoqController::class, 'update'])->name('update');
    Route::delete('/{id}', [BoqController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/history', [BoqController::class, 'history'])->name('history')->whereNumber('id');
});


Route::prefix('/api')
    ->name('api.')
    ->group(function () {
        Route::get('/get-data-site', [BusinessRelationSiteController::class, 'getDataSite'])
            ->name('get-data-site');

        Route::get('/get-contact-site/{id}', [BusinessRelationContactController::class, 'getDataContactSite'])
            ->name('get-data-contact-site');

        Route::get('/get-data-br', [BusinessRelationController::class, 'getDataBR'])
            ->name('get-data-br');

        Route::get('/menus', function () {
            return response()->json(config('menus'));
        })->name('menus');
    });




Route::get('/', function () {
    return redirect('/dashboard');
});

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuGroupController;

Route::prefix('menu-groups')->name('menu-groups.')->group(function () {
    Route::get('/',             [MenuGroupController::class, 'index'])->name('index');
    Route::get('/data',         [MenuGroupController::class, 'data'])->name('data');
    Route::get('/create',       [MenuGroupController::class, 'create'])->name('create');
    Route::post('/',            [MenuGroupController::class, 'store'])->name('store');
    Route::get('/{id}',         [MenuGroupController::class, 'show'])->name('show')->whereNumber('id');
    Route::put('/{id}',         [MenuGroupController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/{id}',      [MenuGroupController::class, 'destroy'])->name('destroy')->whereNumber('id');
    Route::get('/{id}/history', [MenuGroupController::class, 'history'])->name('history')->whereNumber('id');
});

Route::get('/api/menus', function () {
    return response()->json(config('menus'));
});

Route::get('/api/menu-groups', function () {
    $groups = \Illuminate\Support\Facades\DB::table('menu_groups')
        ->select('id', 'name')
        ->orderBy('name')
        ->get();
    return response()->json($groups);
});

Route::prefix('users')->name('users.')->group(function () {
    Route::get('/',           [UserController::class, 'index'])->name('index');
    Route::get('/data',       [UserController::class, 'data'])->name('data');
    Route::get('/select2',    [UserController::class, 'select2'])->name('select2');
    Route::get('/create',     [UserController::class, 'create'])->name('create');
    Route::post('/',          [UserController::class, 'store'])->name('store');
    Route::get('/{id}',       [UserController::class, 'show'])->name('show')->whereNumber('id');
    Route::put('/{id}',       [UserController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/{id}',    [UserController::class, 'destroy'])->name('destroy')->whereNumber('id');
    Route::get('/{id}/history', [UserController::class, 'history'])->name('history')->whereNumber('id');
});

Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/',             [DashboardController::class, 'index'])->name('index');
    Route::get('/summary',      [DashboardController::class, 'summary'])->name('summary');
    Route::get('/so-per-month', [DashboardController::class, 'soPerMonth'])->name('soPerMonth');
});

Route::prefix('/wilayah')->name('wilayah.')->group(function () {
    Route::get('/provinces', [WilayahController::class, 'provinces'])->name('provinces');
    Route::get('/children',  [WilayahController::class, 'children'])->name('children');
});

// Update redirect root ke dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});
