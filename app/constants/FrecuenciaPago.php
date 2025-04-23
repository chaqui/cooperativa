<?

namespace App\Constants;

class FrecuenciaPago
{
    public static $MENSUAL =  422;
    public static $TRIMESTRAL =  423;
    public static $SEMESTRAL =  424;

    public function getFrecuenciaPago($frecuencia)
    {
        switch ($frecuencia) {
            case self::$MENSUAL:
                return 1;
            case self::$TRIMESTRAL:
                return 3;
            case self::$SEMESTRAL:
                return 6;
            default:
                return 1;
        }
    }
}
