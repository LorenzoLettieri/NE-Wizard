<?php

use App\Http\Controllers\UsersController;
use App\Http\Controllers\WorkController;
use Illuminate\Routing\RouteGroup;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get("/users/table", [UsersController::class, "index"])->name("users-table");
Route::get("/users/create", [UsersController::class, "create"])->name("addUser");
Route::post('/users/store', [UsersController::class,'store'])->name('registerUser');
Route::get('/users/edit/{id}', [UsersController::class,'edit'])->name('editUser');
Route::put('/users/update/{id}', [UsersController::class,'update'])->name('updateUser');


Route::get('/works/table', [WorkController::class,'index'])->name('works-table');
Route::get('/works/create', [WorkController::class, 'create'])->name('addWork');
Route::get('/works/edit/{id}', [WorkController::class,'edit'])->name('editWork');
Route::delete('/works/delete/{work}', [WorkController::class, 'delete'])->name('deleteWork');