<?

namespace App\Traits;

use App\Constants\TipoPlazo;


trait Calculos
{
    public  function calcularPlazo($plazo, $tipoPlazo)
    {
        $this->log('El tipo de plazo es ' . $tipoPlazo);
        if ($tipoPlazo == TipoPlazo::$ANUAL) {
            $plazo = $plazo * 12;
        }
        return $plazo;
    }
}
