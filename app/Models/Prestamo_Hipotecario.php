<?php

namespace App\Models;

use App\Constants\FrecuenciaPago;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Loggable;

class Prestamo_Hipotecario extends Model
{
    use Loggable;
    protected $id = 'id';
    protected $table = 'prestamo_hipotecarios';
    protected $fillable = [
        'dpi_cliente',
        'monto',
        'interes',
        'plazo',
        'fecha_inicio',
        'fecha_fin',
        'estado_id',
        'propiedad_id',
        'tipo_plazo',
        'fiador_dpi',
        'destino',
        'uso_prestamo',
        'tipo_garante',
        'frecuencia_pago',
        'id_usuario',
        'parentesco',
        'codigo',
        'gastos_formalidad',
        'gastos_administrativos',
        'cuota',
        'monto_liquido',
        'existente',
        'saldo_existente',
    ];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class, 'propiedad_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_prestamo');
    }

    public function fiador()
    {
        return $this->belongsTo(Client::class, 'fiador_dpi');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstado::class, 'id_prestamo');
    }

    public function tipoPlazo()
    {
        return $this->belongsTo(Tipo_Plazo::class, 'tipo_plazo');
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }


    public function retiro()
    {
        return $this->hasOne(Retiro::class, 'id_prestamo');
    }

    public function montoLiquido()
    {
        return $this->monto - $this->gastos_administrativos - $this->gastos_formalidad;
    }

    public function intereses()
    {
        return $this->pagos()->sum('interes');
    }

    public function getCuotasPendientes()
    {
        return $this->pagos()->where('realizado', 0)->get();
    }

    public function cuotasPagadas()
    {
        return $this->pagos()->where('realizado', 1)->getAppends();
    }

    public function cuotaActiva()
    {
        return $this->pagos()->where('realizado', 0)->orderBy('fecha', 'asc')->first();
    }

    public function frecuenciaPago()
    {
        $frecuenciaPago = new FrecuenciaPago();
        return $frecuenciaPago->getFrecuenciaPago($this->frecuencia_pago);
    }

    public function totalPagado()
    {
        return $this->pagos()->sum('monto_pagado');
    }


    public function saldoPendiente()
    {
        $pagos = $this->getCuotasPendientes();
        $monto = 0;
        foreach ($pagos as $pago) {
            $monto += $pago->saldoFaltante();
        }
        return $monto;
    }
    public function saldoPendienteConInteresAlDia()
    {
        return $this->saldoPendienteCapital() + $this->saldoPendienteIntereses() + $this->saldoPendientePenalizacion();
    }

    public function saldoPendienteIntereses()
    {
        $pagos = $this->getCuotasPendientes();
        $monto = 0;
        foreach ($pagos as $pago) {
            if ($pago->fecha < now()) {
                $monto += $pago->interesFaltante();
            }
        }
        return $monto;
    }

    public function saldoPendienteCapital()
    {
        $pagos = $this->getCuotasPendientes();
        $monto = 0;
        foreach ($pagos as $pago) {
            $monto += $pago->capitalFaltante();
        }
        return $monto;
    }
    public function saldoPendientePenalizacion()
    {
        $pagos = $this->getCuotasPendientes();
        $monto = 0;
        foreach ($pagos as $pago) {
            $monto += $pago->penalizacionFaltante();
        }
        return $monto;
    }

    public function capitalPagado()
    {
        return $this->pagos()->sum('capital_pagado');
    }

    public function interesesPagados()
    {
        return $this->pagos()->sum('interes_pagado');
    }

    public function morosidad()
    {
        $cuotasPendientes = $this->cuotasPendientesAlaFecha()->count();
        if ($cuotasPendientes > 3) {
            return "Alta morosidad";
        } elseif ($cuotasPendientes === 3) {
            return "Morosidad moderada";
        } elseif ($cuotasPendientes === 2) {
            return "Morosidad baja";
        } elseif ($cuotasPendientes === 1 && now()->day > 5) {
            return "Morosidad mínima";
        } else {
            return "Sin morosidad";
        }
    }


    public function cuotasPendientesAlaFecha()
    {
        $fecha = now()->startOfMonth()->addDays(5);
        return $this->pagos()->where('realizado', 0)->where('fecha', '<=', $fecha)->get();
    }

    public function tieneCuotaInvalida()
    {
        $cuota = $this->pagos()->where('numero_pago_prestamo', 0)->first();
        if ($cuota) {
            return true;
        }
        return false;
    }
}
