<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CuentaInternaService;
use App\Http\Resources\Caja as CajaResource;

class CajaController extends Controller
{
    private $cuentaInternaService;

    public function __construct(CuentaInternaService $cuentaInternaService)
    {
        $this->cuentaInternaService = $cuentaInternaService;
    }

    public function index()
    {
        $cuentas = $this->cuentaInternaService->getAllCuentas();
        return CajaResource::collection($cuentas);
    }
}
