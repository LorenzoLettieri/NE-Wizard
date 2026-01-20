<?php

use App\Http\Controllers\OperatorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WorkController;
use Illuminate\Routing\RouteGroup;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
});


Route::group(['middleware' => ['role:admin']], function () {
    Route::get("/users/table", [UsersController::class, "index"])->name("users-table");
    Route::get("/users/accounts/table", [UsersController::class, "accountsTable"])->name("accounts-table");
    Route::get("/users/create", [UsersController::class, "create"])->name("addUser");
    Route::post('/users/store', [UsersController::class, 'store'])->name('registerUser');
    Route::get('/users/edit/{id}', [UsersController::class, 'edit'])->name('editUser');
    Route::put('/users/update/{id}', [UsersController::class, 'update'])->name('updateUser');
    Route::get('/timesheets/dashboard', [\App\Http\Controllers\AdminTimesheetController::class, 'index'])->name('admin.timesheets');
    Route::get('/admin/timesheets/export', [\App\Http\Controllers\AdminTimesheetController::class, 'export'])->name('admin.timesheets.export');

});

Route::group(['middleware' => ['role:admin|supervisor']], function () {
    Route::get('/works/table', [WorkController::class, 'index'])->name('works-table');
    Route::get('/works/create', [WorkController::class, 'create'])->name('addWork');
    Route::get('/works/edit/{id}', [WorkController::class, 'edit'])->name('editWork');
    Route::delete('/works/delete/{work}', [WorkController::class, 'delete'])->name('deleteWork');
});
Route::get('/exports/works', [WorkController::class, 'download'])->name('exports.works');

Route::group(['middleware' => ['permission:get works']], function () {
    Route::get('/operator/table', [OperatorController::class, 'index'])->name('operator-table');
});
Route::group(['middleware' => ['role:operator|supervisor']], function () {
    Route::get('/operator/timesheet', [\App\Http\Controllers\OperatorTimesheetController::class, 'index'])->name('operator-timesheet');
});
Route::group(['middleware' => ['role:admin|GBX|GBX Supervisor']], function () {
    Route::get('/gbxes/table', [\App\Http\Controllers\GbxController::class, 'index'])->name('gbxes-table');
    Route::get('/gbxes/create', [\App\Http\Controllers\GbxController::class, 'create'])->name('addGbx');
    Route::delete('/gbxes/delete/{gbx}', [\App\Http\Controllers\GbxController::class, 'delete'])->name('deleteGbx');
    Route::get('/exports/gbxes', [\App\Http\Controllers\GbxController::class, 'download'])->name('exports.gbxes')->middleware('role:admin');
});

Route::group(['middleware' => ['role:admin|permessi ente']], function () {
    Route::get('/permessi-ente/table', [\App\Http\Controllers\PermessoEnteController::class, 'index'])->name('permessi-ente.table');
    Route::get('/permessi-ente/create', [\App\Http\Controllers\PermessoEnteController::class, 'create'])->name('addPermesso');
    Route::get('/permessi-ente/edit/{id}', [\App\Http\Controllers\PermessoEnteController::class, 'edit'])->name('editPermesso');
    Route::delete('/permessi-ente/delete/{permesso}', [\App\Http\Controllers\PermessoEnteController::class, 'delete'])->name('deletePermesso');
    Route::get('/exports/permessi-ente', [\App\Http\Controllers\PermessoEnteController::class, 'download'])->name('exports.permessi-ente')->middleware('role:admin');
});
