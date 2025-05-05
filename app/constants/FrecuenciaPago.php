<?

namespace App\Constants;

class FrecuenciaPago
{
    public static $MENSUAL =  422;
    public static $TRIMESTRAL =  423;
    public static $SEMESTRAL =  424;

    public static $ANUAL =  431;
    public static $UNICA =  432; //Vencimiento

    public function getFrecuenciaPago($frecuencia)
    {
        switch ($frecuencia) {
            case self::$MENSUAL:
                return 1;
            case self::$TRIMESTRAL:
                return 3;
            case self::$SEMESTRAL:
                return 6;
            case self::$ANUAL:
                return 12;
            case self::$UNICA:
                return 0;
            default:
                return 1;
        }
    }
}
