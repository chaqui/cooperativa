<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCuentaInterna extends Model
{
    use HasFactory;

    protected $table = 'tipo_cuenta_interna';

    protected $fillable = [
        'nombre_banco',
        'tipo_cuenta',
        'numero_cuenta',
        'saldo_inicial',
    ];

    public function cuentaInternas()
    {
        return $this->hasMany(Cuenta_Interna::class, 'tipo_cuenta_interna_id');
    }
    public function retiros()
    {
        return $this->hasMany(Retiro::class, 'tipo_cuenta_interna_id');
    }
    public function depositos()
    {
        return $this->hasMany(Deposito::class, 'tipo_cuenta_interna_id');
    }

    public function ingresos(){
        return $this->cuentaInternas()->sum('ingreso');
    }

    public function egresos(){
        return $this->cuentaInternas()->sum('egreso');
    }

    public function saldo(){
        return $this->ingresos() - $this->egresos();
    }
}
