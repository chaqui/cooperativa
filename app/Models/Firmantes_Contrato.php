<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Firmantes_Contrato extends Model
{
    protected $table = 'firmantes_contratos';
    protected $fillable = [ 'dpi_firmante', 'id_contrato'];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'id_contrato');
    }
}
