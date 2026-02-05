<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoRollback extends Model
{
    protected $table = 'historico_rollbacks';

    protected $fillable = [
        'prestamo_hipotecario_id',
        'datos_anteriores',
        'datos_nuevos',
        'razon',
        'fecha_autorizacion',
        'user_id',
        'rollback_id'
    ];

    public function prestamoHipotecario()
    {
        return $this->belongsTo(Prestamo_Hipotecario::class, 'prestamo_hipotecario_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rollback()
    {
        return $this->belongsTo(Rollback_prestamo::class, 'rollback_id');
    }
}
