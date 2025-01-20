<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inversion extends Model
{
    protected $table = 'inversiones';
    protected $fillable = [
        'monto',
        'interes',
        'plazo',
        'id_estado',
        'tipo_taza',
        'tipo_plazo',
        'tipo_inversion',
        'cuenta_recaudadora',
        'dpi_cliente',
        'fecha'
    ];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

    public function estado()
    {
        return $this->belongsTo(Estado_Inversion::class, 'id_estado');
    }

    public function tipoTaza()
    {
        return $this->belongsTo(Tipo_Tasa_Interes::class, 'tipo_taza');
    }

    public function tipoPlazo()
    {
        return $this->belongsTo(Tipo_Plazo::class, 'tipo_plazo');
    }

    public function tipoInversion()
    {
        return $this->belongsTo(Tipo_Inversion::class, 'tipo_inversion');
    }

    public function pagosInversion()
    {
        return $this->hasMany(Pago_Inversion::class, 'inversion_id');
    }

    public function cuentaRecaudadora()
    {
        return $this->belongsTo(Cuenta_Bancaria::class, 'cuenta_recaudadora');
    }
}
