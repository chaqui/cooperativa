<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposito extends Model
{
    protected $table = 'depositos';

    protected $fillable = [
        'id',
        'tipo_documento',
        'numero_documento',
        'imagen',
        'monto',
        'inversion_id',
        'id_pago',
        'tipo_cuenta_interna_id',
        'motivo',
    ];

    public static function crear($datos)
    {
        $deposito = new Deposito();
        $deposito->tipo_documento = null;
        $deposito->numero_documento = null;
        $deposito->monto = $datos['monto'];
        $deposito->id_inversion = $datos['id_inversion'] ?? null;
        $deposito->id_pago = $datos['id_pago'] ?? null;
        $deposito->realizado = false;
        $deposito->imagen = $datos['imagen'] ?? null;
        $deposito->tipo_cuenta_interna_id = null;
        $deposito->motivo = $datos['motivo'];
        return $deposito;
    }

    public function dataCuenta($interes = 0)

    {
        $descripcion = "Depósito realizado: " . ($this->motivo ?? 'No especificado') .
            " | Documento: " . ($this->tipo_documento ?? 'No especificado') .
            " | Número: " . ($this->numero_documento ?? 'No especificado');
        return [
            'ingreso' => $this->monto,
            'egreso' => 0,
            'capital' => $this->monto - ($interes ?? 0),
            'interes' => $interes ?? 0,
            'descripcion' => $descripcion,
            'tipo_cuenta_interna_id' => $this->tipo_cuenta_interna_id,
        ];
    }


    public function depositar($data)
    {
        $this->realizado = true;
        $this->tipo_documento = $data['tipo_documento'];
        $this->numero_documento = $data['numero_documento'];
        $this->tipo_cuenta_interna_id = $data['id_cuenta'];
        $this->imagen = $data['imagen'] ?? $this->imagen;
    }

    public function inversion()
    {
        return $this->belongsTo(Inversion::class, 'inversion_id');
    }

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'id_pago');
    }

    public function tipoCuentaInterna()
    {
        return $this->belongsTo(TipoCuentaInterna::class, 'tipo_cuenta_interna_id');
    }
}
