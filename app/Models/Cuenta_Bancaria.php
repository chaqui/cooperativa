<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuenta_Bancaria extends Model
{
    protected $table = 'cuenta__bancarias';
    protected $primaryKey = 'numero_cuenta';
    protected $fillable = ['numero_cuenta', 'nombre_banco','tipo_cuenta','dpi_cliente'];
    public $timestamps = false;


    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

    public static function generateCuentaBancaria($data)
    {
        $cuentaBancaria = new Cuenta_Bancaria();
        $cuentaBancaria->numero_cuenta = $data['numero_cuenta'];
        $cuentaBancaria->nombre_banco = $data['nombre_banco'];
        $cuentaBancaria->tipo_cuenta = $data['tipo_cuenta'];
        $cuentaBancaria->dpi_cliente = $data['dpi_cliente'];

        return $cuentaBancaria;
    }

}
