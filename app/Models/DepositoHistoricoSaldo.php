<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class DepositoHistoricoSaldo extends Model
{
    protected $table = 'depositos_historico_saldo';
    protected $fillable = [
        'id_deposito',
        'id_historico_saldo'
    ];

    public static function createHistoricoSaldo($idDeposito, $idHistoricoSaldo)
    {
        return self::create([
            'id_deposito' => $idDeposito,
            'id_historico_saldo' => $idHistoricoSaldo
        ]);
    }

    public function deposito()
    {
        return $this->belongsTo(Deposito::class, 'id_deposito');
    }

    public function historicoSaldo()
    {
        return $this->belongsTo(historico_saldo::class, 'id_historico_saldo');
    }
}
