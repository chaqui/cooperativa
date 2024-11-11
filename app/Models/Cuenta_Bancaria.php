<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuenta_Bancaria extends Model
{
    protected $table = 'cuenta__bancarias';
    protected $primaryKey = 'numero_cuenta';
    protected $fillable = ['numero_cuenta', 'nombre_banco'];
    public $timestamps = false;

    public function tipo_cuenta()
    {
        return $this->belongsTo(Tipo_Cuenta::class, 'tipo_cuenta');
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

}
