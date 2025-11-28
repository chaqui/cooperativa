<?php

namespace App\Models;

use App\Constants\FrecuenciaPago;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Loggable;
use App\Constants\EstadoPrestamo;

class Prestamo_Hipotecario extends Model
{
    use Loggable;
    protected $primaryKey = 'id';
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
        'fecha_fin_nueva',
        'motivo_cancelacion',
        'fecha_cancelacion',
    ];

    protected $dates = [
        'fecha_inicio',
        'fecha_fin',
        'fecha_fin_nueva',
        'fecha_cancelacion',
        'created_at',
        'updated_at'
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
        return $this->pagos()->where('realizado', 0)->orderBy('numero_pago_prestamo', 'asc')->first();
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
        $monto = 0.0;
        foreach ($pagos as $pago) {
            $saldoFaltante = $pago->saldoFaltante();
            $monto += $saldoFaltante;
        }
        // Aplicar redondeo robusto
        $resultado = round($monto, 2);
        $diferencia = abs($resultado - round($resultado));
        if ($diferencia < 0.01 && $diferencia > 0.004) {
            $resultado = round($resultado);
        }
        return $resultado;
    }

    public function saldoPendienteConInteresAlDia()
    {
        return round($this->saldoPendienteCapital() + $this->saldoPendienteIntereses() + $this->saldoPendientePenalizacion(), 2);
    }

    public function saldoPendienteIntereses()
    {
        $pagos = $this->getCuotasPendientes();
        $monto = 0.0;
        foreach ($pagos as $pago) {
            if ($pago->fecha < now()) {
                $interesFaltante = $pago->interesFaltante();
                $monto += $interesFaltante;
            }
        }
        // Aplicar redondeo robusto
        $resultado = round($monto, 2);
        $diferencia = abs($resultado - round($resultado));
        if ($diferencia < 0.01 && $diferencia > 0.004) {
            $resultado = round($resultado);
        }
        return $resultado;
    }

    public function saldoPendienteCapital()
    {
        // El saldo pendiente de capital es el monto original menos el capital pagado
        return round($this->monto - $this->capitalPagado(), 2);
    }
    public function saldoPendientePenalizacion()
    {
        $pagos = $this->getCuotasPendientes();
        $monto = 0.0;
        foreach ($pagos as $pago) {
            $penalizacionFaltante = $pago->penalizacionFaltante();
            $monto += $penalizacionFaltante;
        }
        // Aplicar redondeo robusto
        $resultado = round($monto, 2);
        $diferencia = abs($resultado - round($resultado));
        if ($diferencia < 0.01 && $diferencia > 0.004) {
            $resultado = round($resultado);
        }
        return $resultado;
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

    public function depositos()
    {
        $pagos = $this->pagos()->get();
        $this->log("El préstamo {$this->codigo} tiene " . count($pagos) . " pagos asociados.");
        $depositos = [];
        foreach ($pagos as $pago) {
            $depositos = array_merge($depositos, $pago->depositos->toArray());
        }
        return $depositos;
    }

    /**
     * Verifica si el préstamo está cancelado
     *
     * @return bool
     */
    public function estaCancelado(): bool
    {
        return !empty($this->fecha_cancelacion);
    }

    /**
     * Verifica si el préstamo puede ser cancelado
     *
     * @return bool
     */
    public function puedeSerCancelado(): bool
    {
        return !$this->estaCancelado();
    }

    /**
     * Obtiene el motivo de cancelación si existe
     *
     * @return string|null
     */
    public function getMotivoCancelacion(): ?string
    {
        return $this->motivo_cancelacion;
    }

    /**
     * Obtiene la fecha de cancelación formateada
     *
     * @return string|null
     */
    public function getFechaCancelacionFormateada(): ?string
    {
        return $this->fecha_cancelacion ? $this->fecha_cancelacion->format('Y-m-d H:i:s') : null;
    }

    public function getMotivoRechazo()
    {
        $historial = HistorialEstado::where('id_prestamo', $this->id)
            ->where('id_estado', EstadoPrestamo::$RECHAZADO)
            ->orderBy('created_at', 'desc')
            ->first();

        return $historial ? $historial->razon : null;
    }

    public function propiedadAsociada()
    {
        return $this->belongsTo(Propiedad::class, 'propiedad_id');
    }

    public function diasDeAtraso()
    {
        $cuotaActiva = $this->cuotaActiva();
        if (!$cuotaActiva) {
            return 0;
        }
        $fechaHoy = now();
        $fechaCuota = \Carbon\Carbon::parse($cuotaActiva->fecha);
        $fechaLimite = $fechaCuota->copy()->addDays(5);
        if ($fechaHoy <= $fechaLimite) {
            return 0;
        }
        return (int) $fechaLimite->diffInDays($fechaHoy);
    }
}
