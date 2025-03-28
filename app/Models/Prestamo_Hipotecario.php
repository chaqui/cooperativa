<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo_Hipotecario extends Model
{
    protected $id = 'id';
    protected $table = 'prestamo_hipotecarios';
    protected $fillable = [
        'dpi_cliente',
        'monto',
        'interes',
        'plazo',
        'fecha_inicio',
        'fecha_fin',
        'estado_id',
        'propiedad_id',
        'tipo_plazo',
        'fiador_dpi',
        'destino',
        'uso_prestamo',
        'tipo_garante',
        'frecuencia_pago',
        'id_usuario',
        'parentesco',
        'codigo',
        'gastos_formalidad',
        'gastos_administrativos',
        'cuota',
        'monto_liquido',
    ];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class, 'propiedad_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_prestamo');
    }

    public function fiador()
    {
        return $this->belongsTo(Client::class, 'fiador_dpi');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstado::class, 'id_prestamo');
    }

    public function tipoPlazo()
    {
        return $this->belongsTo(Tipo_Plazo::class, 'tipo_plazo');
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function cuotasPendientes()
    {
        return $this->hasMany(Pago::class, 'id_prestamo')->where('realizado', 0);
    }

    public function retiro()
    {
        return $this->hasOne(Retiro::class, 'id_prestamo');
    }

    public function montoLiquido(){
        return $this->monto - $this->gastos_administrativos - $this->gastos_formalidad;
    }

    public function intereses()
    {
        return $this->pagos()->sum('interes');
    }
}
