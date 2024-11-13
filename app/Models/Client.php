<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';
    protected $primaryKey = 'dpi';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    protected $fillable = [
        'dpi',
        'nombres',
        'apellidos',
        'telefono',
        'correo',
        'direccion',
        'ciudad',
        'departamento',
        'estado_civil',
        'genero',
        'nivel_academico',
        'profesion',
        'fecha_nacimiento',
        'etado_cliente',
        'limite_credito',
        'credito_disponible',
        'ingresos_mensuales',
        'egresos_mensuales',
        'capacidad_pago',
        'calificacion',
        'fecha_actualizacion_calificacion',
    ];

    public function estadoCliente()
    {
        return $this->belongsTo(Estado_Cliente::class, 'etado_cliente');
    }

    public function cuentasBancarias()
    {
        return $this->hasMany(Cuenta_Bancaria::class, 'dpi_cliente');
    }

    public function prestamosHipotecarios()
    {
        return $this->hasMany(Prestamo_Hipotecario::class, 'dpi_cliente');
    }

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'dpi_cliente');
    }
}
