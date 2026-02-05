<?php

namespace App\Constants;


class RollBackCampos
{
    public static $cuotas = 'cuotas_hipotecarias';
    public static $fecha_fin_prestamo = 'fecha_fin_prestamo_hipotecario';

    public static $interesPagado = 'interes_pagado_historico_saldo';

    public static $depositos = 'depositos_hipotecarios';

    public static $cuentasInternas = 'cuentas_internas';

    public static $impuestosTransacciones = 'impuestos_transacciones';

    public static function getCampos()
    {
        return [
            self::$cuotas,
            self::$fecha_fin_prestamo,
            self::$interesPagado,
            self::$depositos,
            self::$cuentasInternas,
            self::$impuestosTransacciones
        ];
    }

    public static function esCampoValido($campo)
    {
        return in_array($campo, self::getCampos());
    }
}
