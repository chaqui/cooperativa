<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'imagen',
        'monto',
        'id_inversion',
        'id_pago',
        'realizado',
    ];

    public function inversion()
    {
        return $this->belongsTo(Inversion::class, 'id_inversion');
    }

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'id_pago');
    }
}
