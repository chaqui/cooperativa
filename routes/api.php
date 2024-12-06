<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InversionController;

Route::resource('clients', ClientController::class);

//inversiones
Route::resource('inversiones', InversionController::class);
Route::get('inversiones/{id}/cuotas', [InversionController::class, 'cuotas']);
