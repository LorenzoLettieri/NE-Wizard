<?php

use App\Http\Controllers\OperatorController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WorkController;
use Illuminate\Routing\RouteGroup;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');


Route::group(['middleware' => ['role:admin']], function (){
    Route::get("/users/table", [UsersController::class, "index"])->name("users-table");
    Route::get("/users/accounts/table", [UsersController::class, "accountsTable"])->name("accounts-table");
    Route::get("/users/create", [UsersController::class, "create"])->name("addUser");
    Route::post('/users/store', [UsersController::class,'store'])->name('registerUser');
    Route::get('/users/edit/{id}', [UsersController::class,'edit'])->name('editUser');
    Route::put('/users/update/{id}', [UsersController::class,'update'])->name('updateUser');
});

Route::group(['middleware' => ['role:admin|supervisor']], function (){
    Route::get('/works/table', [WorkController::class,'index'])->name('works-table');
    Route::get('/works/create', [WorkController::class, 'create'])->name('addWork');
    Route::get('/works/edit/{id}', [WorkController::class,'edit'])->name('editWork');
    Route::delete('/works/delete/{work}', [WorkController::class, 'delete'])->name('deleteWork');
});

Route::group(['middleware' => ['permission:get works']], function (){
    Route::get('/operator/table', [OperatorController::class, 'index'])->name('operator-table');
});
