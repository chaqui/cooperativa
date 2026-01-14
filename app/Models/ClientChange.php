<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientChange extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'clients_changes';

    protected $fillable = [
        'cambios',
        'dpi_cliente',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente', 'dpi');
    }
}
