<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\Http\Middleware\Authenticate;

use App\Constants\Roles;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TipoPlazoController;
use App\Http\Controllers\InversionController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\PropiedadController;
use App\Http\Controllers\FotografiaController;
use App\Http\Controllers\CuentaBancariaController;
use App\Http\Controllers\PrestamoController;

$rolesEdicion = implode('|', [Roles::$ADMIN, Roles::$ASESOR]);
$rolesSoloLectura = implode('|', [Roles::$ADMIN, Roles::$ASESOR, Roles::$CAJERO]);

//clientes
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('clients', [ClientController::class, 'store']);
    Route::put('clients/{id}', [ClientController::class, 'update']);
    Route::delete('clients/{id}', [ClientController::class, 'destroy']);
    Route::put('clients/inactivar/{id}', [ClientController::class, 'inactivar']);
    Route::post('clients/{id}/fotografia', [ClientController::class, 'uploadFoto']);
    Route::get('clients/{id}/propiedades', [ClientController::class, 'propiedades']);
    Route::get('clients/{id}/prestamos', [ClientController::class, 'prestamos']);
});

Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('clients', [ClientController::class, 'index']);
    Route::get('clients/{id}', [ClientController::class, 'show']);
    Route::get('clients/{id}/cuentas-bancarias', [ClientController::class, 'cuentasBancarias']);
    Route::get('clients/{id}/inversiones', [ClientController::class, 'inversiones']);
    Route::get('clients/{id}/references', [ClientController::class, 'referencias']);
    Route::get('clients/{id}/pdf', [ClientController::class, 'generateClientPdf']);
});

//inversiones
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('inversiones', [InversionController::class, 'store']);
    Route::put('inversiones/{id}', [InversionController::class, 'update']);
    Route::delete('inversiones/{id}', [InversionController::class, 'destroy']);
});

Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('inversiones', [InversionController::class, 'index']);
    Route::get('inversiones/{id}', [InversionController::class, 'show']);
    Route::get('inversiones/{id}/cuotas', [InversionController::class, 'cuotas']);
});


//pagos
Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::post('pagar-cuota/{id}', [CuotaController::class, 'pagarCuota']);
});


//cuentas
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('cuentas-bancarias', [CuentaBancariaController::class, 'store']);
    Route::put('cuentas-bancarias/{id}', [CuentaBancariaController::class, 'update']);
    Route::delete('cuentas-bancarias/{id}', [CuentaBancariaController::class, 'destroy']);
});

Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('cuentas-bancarias', [CuentaBancariaController::class, 'index']);
    Route::get('cuentas-bancarias/{id}', [CuentaBancariaController::class, 'show']);

    //tipos de plazo
    Route::get('tipos-plazo', [TipoPlazoController::class, 'index']);
    Route::get('tipos-plazo/{id}', [TipoPlazoController::class, 'show']);
});

//referencias
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('references', [ReferenceController::class, 'store']);
    Route::put('references/{id}', [ReferenceController::class, 'update']);
    Route::delete('references/{id}', [ReferenceController::class, 'destroy']);
});

Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('references', [ReferenceController::class, 'index']);
    Route::get('references/{id}', [ReferenceController::class, 'show']);
});

//fotografia
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::delete('fotografia/', [FotografiaController::class, 'deleteFotografia']);
});
Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('fotografia/', [FotografiaController::class, 'getFotografia']);
});

//Users
Route::resource('users', UserController::class)->middleware(CheckRole::class . ':' . Roles::$ADMIN);
Route::post('users/{id}/inactivate', [UserController::class, 'inactivate'])->middleware(CheckRole::class . ':' . Roles::$ADMIN);
Route::post('users/{id}/change-password', [UserController::class, 'changePassword'])->middleware(CheckRole::class . ':' . Roles::$ADMIN);

//Auth
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware(Authenticate::class);
Route::post('validate-token', [AuthController::class, 'validateToken'])->middleware(Authenticate::class);

//Roles
Route::middleware(CheckRole::class . ':' . Roles::$ADMIN)->group(function () {
    Route::resource('roles', RoleController::class);
});

//propiedades
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('propiedades', [PropiedadController::class, 'store']);
    Route::put('propiedades/{id}', [PropiedadController::class, 'update']);
    Route::delete('propiedades/{id}', [PropiedadController::class, 'destroy']);
});

//prestamos
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('prestamos', [PrestamoController::class, 'store']);
    Route::put('prestamos/{id}', [PrestamoController::class, 'update']);
    Route::get('prestamos',action: [PrestamoController::class, 'index']);
    Route::delete('prestamos/{id}', [PrestamoController::class, 'destroy']);
    Route::put('prestamos/inactivar/{id}', [PrestamoController::class, 'inactivar']);
    Route::get('prestamos/{id}/historial', [PrestamoController::class, 'historial']);
    Route::post('prestamos/{id}/cambiar-estado', [PrestamoController::class, 'cambiarEstado']);
    Route::get('estados/{estado}/prestamos', [PrestamoController::class, 'prestamosByEstado']);
});
