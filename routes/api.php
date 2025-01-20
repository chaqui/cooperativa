<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InversionController;
use App\Http\Controllers\CuentaBancariaController;
use App\Http\Controllers\CuotaController;

//clientes
Route::resource('clients', ClientController::class);
Route::get('clients/{id}/cuentas-bancarias', [ClientController::class, 'cuentasBancarias']);

//inversiones
Route::resource('inversiones', InversionController::class);
Route::get('inversiones/{id}/cuotas', [InversionController::class, 'cuotas']);

//pagos
Route::post('pagar-cuota/{id}', [CuotaController::class, 'pagarCuota']);

//cuentas
Route::resource('cuentas-bancarias', CuentaBancariaController::class);
