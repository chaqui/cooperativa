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
