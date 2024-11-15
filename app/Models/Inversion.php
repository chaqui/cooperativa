<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inversion extends Model
{
    protected $table = 'inversiones';
    protected $fillable = ['monto', 'interes', 'plazo', 'fecha_inicio', 'fecha_fin', 'cliente', 'estado'];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'cliente');
    }

    public function estado()
    {
        return $this->belongsTo(Estado_Inversion::class, 'estado');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'inversion');
    }

    public function tipo_taza()
    {
        return $this->belongsTo(Tipo_Tasa_Interes::class, 'tipo_taza');
    }

    public function tipo_plazo()
    {
        return $this->belongsTo(Tipo_Plazo::class, 'tipo_plazo');
    }

    public function tipo_inversion()
    {
        return $this->belongsTo(Tipo_Inversion::class, 'tipo_inversion');
    }

    public function pagos_inversion()
    {
        return $this->hasMany(Pago_Inversion::class, 'inversion_id');
    }


}
