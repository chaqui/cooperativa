<?php
 namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuenta_Interna extends Model{
    protected $table = 'cuenta_interna';
    protected $primaryKey = 'id';
    protected $fillable = ['ingreso', 'egreso', 'descripcion'];
    public $timestamps = true;

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'id_cliente');
    }

    public static function generateCuentaInterna($data)
    {
        $cuentaInterna = new Cuenta_Interna();
        $cuentaInterna->id_cuenta_interna = $data['id_cuenta_interna'];
        $cuentaInterna->saldo = $data['saldo'];
        $cuentaInterna->id_cliente = $data['id_cliente'];

        return $cuentaInterna;
    }

}
