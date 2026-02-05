<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rollback_prestamo extends Model
{
    protected $table = 'rollback_prestamos';

    protected $fillable = [
        'prestamo_hipotecario_id',
        'datos_a_eliminar',
        'datos_a_modificar'
    ];

    public function historicos()
    {
        return $this->hasMany(HistoricoRollback::class, 'rollback_id');
    }
}
