<?php

use App\Http\Controllers\OperatorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DecommissioningController;
use App\Http\Controllers\Admin\OperatorWorkspaceController;
use App\Http\Controllers\ChunkedMediaUploadController;
use App\Http\Controllers\MediaDownloadController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WorkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/media/{media}/download', MediaDownloadController::class)->name('media.download');
    Route::post('/media/uploads/process', [ChunkedMediaUploadController::class, 'process'])->name('media.uploads.process');
    Route::patch('/media/uploads/process/{session}', [ChunkedMediaUploadController::class, 'patch'])->name('media.uploads.patch');
    Route::match(['HEAD'], '/media/uploads/process/{session}', [ChunkedMediaUploadController::class, 'head'])->name('media.uploads.head');
    Route::delete('/media/uploads/revert', [ChunkedMediaUploadController::class, 'revert'])->name('media.uploads.revert');
});


Route::group(['middleware' => ['role:admin']], function () {
    Route::get("/users/accounts/table", [UsersController::class, "accountsTable"])->name("accounts-table");
    Route::get("/users/create", [UsersController::class, "create"])->name("addUser");
    Route::post('/users/store', [UsersController::class, 'store'])->name('registerUser');
    Route::get('/users/edit/{id}', [UsersController::class, 'edit'])->name('editUser');
    Route::put('/users/update/{id}', [UsersController::class, 'update'])->name('updateUser');
    Route::get('/timesheets/dashboard', [\App\Http\Controllers\AdminTimesheetController::class, 'index'])->name('admin.timesheets');
    Route::get('/admin/timesheets/export', [\App\Http\Controllers\AdminTimesheetController::class, 'export'])->name('admin.timesheets.export');
    Route::get('/admin/base-tables', [\App\Http\Controllers\Admin\BaseTablesController::class, 'index'])->name('admin.base-tables');
    Route::get('/admin/operators/{user}/view', [OperatorWorkspaceController::class, 'show'])->name('admin.operator-workspace');

});

Route::middleware(['auth', 'role:admin|supervisor'])->group(function () {
    Route::get("/users/table", [UsersController::class, "index"])->name("users-table");
    Route::view('/reports/operators', 'reports.operators')->name('reports.operators');
    Route::get('/works/table', [WorkController::class, 'index'])->name('works-table');
    Route::get('/works/create', [WorkController::class, 'create'])->name('addWork');
    Route::get('/works/edit/{id}', [WorkController::class, 'edit'])->name('editWork');
    Route::delete('/works/delete/{work}', [WorkController::class, 'delete'])->name('deleteWork');
    Route::get('/exports/works', [WorkController::class, 'download'])->name('exports.works');
});

Route::group(['middleware' => ['permission:get works']], function () {
    Route::get('/operator/table', [OperatorController::class, 'index'])->name('operator-table');
});
Route::group(['middleware' => ['role:operator|supervisor']], function () {
    Route::get('/operator/timesheet', [\App\Http\Controllers\OperatorTimesheetController::class, 'index'])->name('operator-timesheet');
});
Route::middleware(['auth', 'role:admin|GBX|GBX Supervisor'])->group(function () {
    Route::get('/gbxes/table', [\App\Http\Controllers\GbxController::class, 'index'])->name('gbxes-table');
    Route::get('/gbxes/create', [\App\Http\Controllers\GbxController::class, 'create'])->name('addGbx');
    Route::delete('/gbxes/delete/{gbx}', [\App\Http\Controllers\GbxController::class, 'delete'])->name('deleteGbx');
    Route::get('/exports/gbxes', [\App\Http\Controllers\GbxController::class, 'download'])->name('exports.gbxes')->middleware('role:admin');
});

Route::middleware(['auth', 'role:admin|permessi ente'])->group(function () {
    Route::get('/permessi-ente/table', [\App\Http\Controllers\PermessoEnteController::class, 'index'])->name('permessi-ente.table');
    Route::get('/permessi-ente/create', [\App\Http\Controllers\PermessoEnteController::class, 'create'])->name('addPermesso');
    Route::get('/permessi-ente/edit/{id}', [\App\Http\Controllers\PermessoEnteController::class, 'edit'])->name('editPermesso');
    Route::delete('/permessi-ente/delete/{permesso}', [\App\Http\Controllers\PermessoEnteController::class, 'delete'])->name('deletePermesso');
    Route::get('/exports/permessi-ente', [\App\Http\Controllers\PermessoEnteController::class, 'download'])->name('exports.permessi-ente')->middleware('role:admin');
});

Route::middleware(['auth', 'role:admin|Deco'])->group(function () {
    Route::get('/decommissionings/table', [DecommissioningController::class, 'index'])->name('decommissionings.table');
    Route::get('/decommissionings/create', [DecommissioningController::class, 'create'])->name('addDecommissioning');
    Route::get('/decommissionings/edit/{id}', [DecommissioningController::class, 'edit'])->name('editDecommissioning');
    Route::delete('/decommissionings/delete/{decommissioning}', [DecommissioningController::class, 'delete'])->name('deleteDecommissioning');
    Route::get('/exports/decommissionings', [DecommissioningController::class, 'download'])->name('exports.decommissionings')->middleware('role:admin');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::view('/reports/decommissioning', 'reports.decommissioning')->name('reports.decommissioning');
});
