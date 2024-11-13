<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $table = 'contratos';
    protected $fillable = [
        'fecha',
        'numero_contrato',
        'dpi_cliente',
        'id_hipoteca',
        'id_inversion',
        'id_fiducia'
    ];

    public function hipoteca()
    {
        return $this->belongsTo(Prestamo_Hipotecario::class, 'id_hipoteca');
    }

    public function inversion()
    {
        return $this->belongsTo(Inversion::class, 'id_inversion');
    }

    public function fiducia()
    {
        return $this->belongsTo(FIducia::class, 'id_fiducia');
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

    public function firmantes()
    {
        return $this->hasMany(Firmantes_Contrato::class, 'id_contrato');
    }
}
