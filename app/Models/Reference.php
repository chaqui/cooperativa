<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reference extends Model
{
    protected $table = 'references';

    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
        'telefono',
        'tipo',
        'dpi_cliente',
    ];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

}
