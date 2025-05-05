<?

namespace App\Constants;


class PlazoImpuesto
{
    public static $MENSUAL = 'Mensual';
    public static $TRIMESTRAL = 'Trimestral';
    public static $ANUAL = 'Anual';
    

    public static function mesesPlazo($plazo)
    {
        if ($plazo == self::$MENSUAL) {
            return 1;
        } elseif ($plazo == self::$TRIMESTRAL) {
            return 3;
        } elseif ($plazo == self::$ANUAL) {
            return 12;
        } else {
            return 0;
        }
    }
}
