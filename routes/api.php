<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TipoPlazoController;
use App\Http\Controllers\InversionController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\FotografiaController;
use App\Http\Controllers\CuentaBancariaController;

//clientes
Route::resource('clients', ClientController::class);
Route::get('clients/{id}/cuentas-bancarias', [ClientController::class, 'cuentasBancarias']);
Route::get('clients/{id}/inversiones', [ClientController::class, 'inversiones']);
Route::get('clients/{id}/references', [ClientController::class, 'referencias']);
Route::get('clients/{id}/pdf', [ClientController::class, 'generateClientPdf']);
Route::post('clients/{id}/fotografia', [ClientController::class, 'uploadFoto']);
Route::put('clients/inactivar/{id}', [ClientController::class, 'inactivar']);


//inversiones
Route::resource('inversiones', InversionController::class);
Route::get('inversiones/{id}/cuotas', [InversionController::class, 'cuotas']);

//pagos
Route::post('pagar-cuota/{id}', [CuotaController::class, 'pagarCuota']);

//cuentas
Route::resource('cuentas-bancarias', CuentaBancariaController::class);

//tipos de plazo
Route::get('tipos-plazo', [TipoPlazoController::class, 'index']);
Route::get('tipos-plazo/{id}', [TipoPlazoController::class, 'show']);

//referencias
Route::resource('references', ReferenceController::class);

//fotografia
Route::delete('fotografia/', [FotografiaController::class, 'deleteFotografia']);
Route::get('fotografia/', [FotografiaController::class, 'getFotografia']);

//Users
Route::resource('users', UserController::class);

//Auth
Route::post('login', [AuthController::class, 'login']);
