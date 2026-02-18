<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

use App\Constants\Roles;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\TipoPlazoController;
use App\Http\Controllers\InversionController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\PropiedadController;
use App\Http\Controllers\FotografiaController;
use App\Http\Controllers\CuentaBancariaController;
use App\Http\Controllers\DepositoController;
use App\Http\Controllers\RetiroController;
use App\Http\Controllers\TipoCuentaInternaController;
use App\Http\Controllers\DeclaracionController;
use App\Http\Controllers\ImpuestoController;
use App\Http\Controllers\OrientacionController;
use App\Http\Controllers\ExcelTemplateController;
use App\Http\Controllers\ArchivoController;
use App\Http\Controllers\RollBackController;

$rolesEdicion = implode('|', [Roles::$ADMIN, Roles::$ASESOR]);
$rolesSoloLectura = implode('|', [Roles::$ADMIN, Roles::$ASESOR, Roles::$CAJERO, Roles::$SECRETARIA]);

Route::get('clients/excel/download', [ClientController::class, 'exportarExcel'])
    ->middleware(CheckRole::class . ':' . Roles::$ADMIN);
//clientes
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('clients', [ClientController::class, 'store']);
    Route::put('clients/{id}', [ClientController::class, 'update']);
    Route::delete('clients/{id}', [ClientController::class, 'destroy']);
    Route::put('clients/inactivar/{id}', [ClientController::class, 'inactivar']);
    Route::post('clients/{id}/fotografia', [ClientController::class, 'uploadFoto']);
    Route::get('clients/{id}/propiedades', [ClientController::class, 'propiedades']);
    Route::get('clients/{id}/propiedades-sin-prestamo', [ClientController::class, 'getPropiedadSinPrestamo']);
    Route::get('clients/{id}/prestamos', [ClientController::class, 'prestamos']);
    Route::post('clients/{id}/upload-dpi', [ClientController::class, 'guardarDpi']);
});

Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('clients', [ClientController::class, 'index']);
    Route::get('clients/buscar', [ClientController::class, 'buscar']);
    Route::get('clients/{id}', [ClientController::class, 'show']);
    Route::get('clients/{id}/cuentas-bancarias', [ClientController::class, 'cuentasBancarias']);
    Route::get('clients/{id}/inversiones', [ClientController::class, 'inversiones']);
    Route::get('clients/{id}/references', [ClientController::class, 'referencias']);
    Route::get('clients/{id}/pdf', [ClientController::class, 'generateClientPdf']);
    Route::get('clients/{id}/cuotas', [ClientController::class, 'cuotas']);
    Route::get('clients/{id}/beneficiarios', [ClientController::class, 'getBeneficiarios']);
    Route::get('clients/{id}/data-for-pdf', [ClientController::class, 'getDataForPDF']);
    Route::get('clients/{id}/changes-log', [ClientController::class, 'getChangesLog']);
});

//inversiones
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('inversiones', [InversionController::class, 'store']);
    Route::put('inversiones/{id}', [InversionController::class, 'update']);
    Route::delete('inversiones/{id}', [InversionController::class, 'destroy']);
    Route::put('inversiones/{id}/estados', [InversionController::class, 'cambiarEstado']);
});

Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('inversiones', [InversionController::class, 'index']);
    Route::get('inversiones/depositos', [InversionController::class, 'getDepositos']);
    Route::get('inversiones/{id}', [InversionController::class, 'show']);
    Route::get('inversiones/{id}/cuotas', [InversionController::class, 'cuotas']);
    Route::get('inversiones/{id}/estados', [InversionController::class, 'historico']);
    Route::get('inversiones/{id}/depositos', [InversionController::class, 'getDepositosInversion']);
    Route::get('inversiones/{id}/pdf', [InversionController::class, 'generatePdf']);
});


//pagos
Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::put('pagos/{id}', [PagoController::class, 'pagarCuota']);
    Route::get('pagos/{id}/depositos', [PagoController::class, 'obtenerDepositos']);
});

//cuentas bancarias
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
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::post('validate-token', [AuthController::class, 'validateToken'])->middleware('auth:api');

//Roles
Route::middleware(CheckRole::class . ':' . Roles::$ADMIN)->group(function () {
    Route::resource('roles', RoleController::class);
});

//propiedades
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('propiedades', [PropiedadController::class, 'store']);
    Route::put('propiedades/{id}', [PropiedadController::class, 'update']);
    Route::delete('propiedades/{id}', [PropiedadController::class, 'destroy']);
    Route::get('propiedades/{id}', [PropiedadController::class, 'show']);
});

//prestamos
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::get('prestamos', action: [PrestamoController::class, 'index']);
    Route::get('prestamos/buscar', [PrestamoController::class, 'buscar']);
    Route::get('prestamos/buscar-paginado', [PrestamoController::class, 'buscarPaginado']);
    Route::get('prestamos/excel', [PrestamoController::class, 'downloadExcelPrestamo']);
    Route::get('prestamos/retiros', [PrestamoController::class, 'getRetirosPendientes']);
    Route::post('prestamos', [PrestamoController::class, 'store']);
    Route::get('prestamos/{id}', [PrestamoController::class, 'show']);
    Route::put('prestamos/{id}', [PrestamoController::class, 'update']);
    Route::delete('prestamos/{id}', [PrestamoController::class, 'destroy']);
    Route::put('prestamos/{id}', [PrestamoController::class, 'actualizarPrestamo']);
    Route::put('prestamos/inactivar/{id}', [PrestamoController::class, 'inactivar']);
    Route::get('prestamos/{id}/estados', [PrestamoController::class, 'historial']);
    Route::put('prestamos/{id}/estados', [PrestamoController::class, 'cambiarEstado']);
    Route::get('prestamos/{id}/pdf', [PrestamoController::class, 'generatePdf']);
    Route::get('prestamos/{id}/estado-cuenta', [PrestamoController::class, 'getEstadoCuenta']);
    Route::get('prestamos/{id}/estado-cuenta/pdf', [PrestamoController::class, 'generarEstadoCuenta']);
    Route::get('prestamos/{id}/pagos', [PrestamoController::class, 'pagos']);
    Route::post('prestamos/{id}/pagos', [PrestamoController::class, 'pagarCuota']);
    Route::get('prestamos/{id}/depositos/pdf', [PrestamoController::class, 'generarEstadoCuentaDepositos']);
    Route::get('prestamos/{id}/propiedad', [PrestamoController::class, 'getPropiedad']);
    Route::post('prestamos/{id}/rollbacks', [RollBackController::class, 'solicitarRollback']);
});

//estados
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::get('estados/{estado}/prestamos', [PrestamoController::class, 'prestamosByEstado']);
});

//cuotas
Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('cuotas-hoy', [CuotaController::class, 'obtenerCuotasParaPagarHoy']);
    // Búsqueda de préstamos para roles de solo lectura
    Route::get('prestamos/consultar', [PrestamoController::class, 'buscar']);
    Route::get('prestamos/consultar-paginado', [PrestamoController::class, 'buscarPaginado']);
});

//depositos
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('depositos', action: [DepositoController::class, 'crearDepositoyDepositar']);
    Route::get('depositos/excel/por-fecha/{fecha}', [DepositoController::class, 'obtenerDepositosPorFecha']);
    Route::get('depositos/excel', [ExcelTemplateController::class, 'downloadDepositTemplate']);
    Route::put('depositos/{id}', [DepositoController::class, 'depositar']);
    Route::get('depositos/{id}/pdf', [DepositoController::class, 'getPDF']);
});

//retiros
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('retiros', [RetiroController::class, 'crearRetiro']);
    Route::put('retiros/{id}', [RetiroController::class, 'retirar']);
    Route::get('retiros/{id}/pdf', [RetiroController::class, 'getPdf']);
});

//cuentas
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::post('cuentas', [TipoCuentaInternaController::class, 'store']);
    Route::get('cuentas', [TipoCuentaInternaController::class, 'index']);
    Route::get('cuentas/{id}', [TipoCuentaInternaController::class, 'show']);
    Route::get('cuentas/{id}/detalles', [TipoCuentaInternaController::class, 'getDetalles']);
    Route::get('cuentas/{id}/depositos', [TipoCuentaInternaController::class, 'getDepositos']);
    Route::get('cuentas/{id}/retiros', [TipoCuentaInternaController::class, 'getRetiros']);
    Route::get('cuentas/{id}/pdf', [TipoCuentaInternaController::class, 'generarPdfCuentasInternas']);
});

//impuestos
Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('impuestos', [ImpuestoController::class, 'index']);
    Route::get('impuestos/{id}/declaraciones', [ImpuestoController::class, 'getDeclaraciones']);
});

//declaraciones
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::get('declaraciones/{id}', [DeclaracionController::class, 'show']);
    Route::get('declaraciones/{id}/transacciones', [DeclaracionController::class, 'getTransacciones']);
    Route::put('declaraciones/{id}', [DeclaracionController::class, 'declarar']);
});

//orientaciones
Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('orientaciones', [OrientacionController::class, 'index']);
});

//proyectar cuotas
Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('cuotas/{id}/proyectar', [CuotaController::class, 'proyectarCuota']);
});

Route::middleware(CheckRole::class . ':' . $rolesSoloLectura)->group(function () {
    Route::get('archivos', [ArchivoController::class, 'obtenerArchivo']);
});

//rollbacks
Route::middleware(CheckRole::class . ':' . $rolesEdicion)->group(function () {
    Route::get('rollbacks', action: [RollBackController::class, 'index']);
    Route::get('rollbacks/pendientes', [RollBackController::class, 'getRollBacksPendientes']);
});
Route::middleware(CheckRole::class . ':' . Roles::$ADMIN)->group(function () {
    Route::put('rollbacks/{rollBackHistoricoId}/autorizar', [RollBackController::class, 'autorizarRollback']);
});
