<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CuotaController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TipoPlazoController;
use App\Http\Controllers\InversionController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\CuentaBancariaController;

//clientes
Route::resource('clients', ClientController::class);
Route::get('clients/{id}/cuentas-bancarias', [ClientController::class, 'cuentasBancarias']);
Route::get('clients/{id}/inversiones', [ClientController::class, 'inversiones']);
Route::get('clients/{id}/references', [ClientController::class, 'referencias']);

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
