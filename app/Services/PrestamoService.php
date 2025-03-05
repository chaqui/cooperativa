<?php

namespace App\Services;

use App\Constants\EstadoPrestamo;
use App\EstadosPrestamo\ControladorEstado;
use App\Models\Prestamo_Hipotecario;

class PrestamoService
{

    private $controladorEstado;

    public function __construct(ControladorEstado $controladorEstado)
    {
        $this->controladorEstado = $controladorEstado;
    }
    public function create($data)
    {
        $prestamo= Prestamo_Hipotecario::create($data);

        $this->controladorEstado->cambiarEstado($prestamo, EstadoPrestamo::$CREADO);
    }

    public function update($id, $data)
    {
        $prestamo = Prestamo_Hipotecario::find($id);
        $prestamo->update($data);
        return $prestamo;
    }

    public function delete($id)
    {
        $prestamo = Prestamo_Hipotecario::find($id);
        $prestamo->delete();
    }

    public function get($id)
    {
        return Prestamo_Hipotecario::find($id);
    }

    public function all()
    {
        return Prestamo_Hipotecario::all();
    }

    public function cambiarEstado($id, $estado, $razon)
    {
        $prestamo = $this->get($id);
        $this->controladorEstado->cambiarEstado($prestamo, $estado, $razon);
    }
}
