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
        'usuario_modifico',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente', 'dpi');
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'usuario_modifico', 'id');
    }
}
