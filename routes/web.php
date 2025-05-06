<?php

use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get("/users/table", [UsersController::class, "index"])->name("users-table");
Route::get("/users/create", [UsersController::class, "create"])->name("addUser");
Route::post('/users/store', [UsersController::class,'store'])->name('registerUser');
Route::get('/users/edit/{id}', [UsersController::class,'edit'])->name('editUser');
Route::put('/users/update/{id}', [UsersController::class,'update'])->name('updateUser');