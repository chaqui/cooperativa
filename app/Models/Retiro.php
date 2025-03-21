<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Retiro extends Model
{
    protected $fillable = [
        'numero_cuenta',
        'tipo_documento',
        'id_prestamo',
        'id_pago_inversions',
        'numero_documento',
        'monto',
        'motivo',
        'imagen',
        'beneficiario',
        'realizado',
    ];

    public function prestamo()
    {
        return $this->belongsTo(Prestamo_Hipotecario::class, 'id_prestamo');
    }

    public function pagoInversion()
    {
        return $this->belongsTo(Pago_Inversion::class, 'id_pago_inversions');
    }

    public function cuentaBancaria()
    {
        return $this->belongsTo(Cuenta_Bancaria::class, 'numero_cuenta', 'numero_cuenta');
    }
}
