<?


namespace App\Services;

use App\Models\Pago_Inversion;
use App\Traits\Loggable;


class PagoInversionService
{
    use Loggable;

    public function pagar($id)
    {
        $this->log('Iniciando el proceso de pago de la inversión con ID: ' . $id);
        $pago = $this->getPagoInversion($id);
        $pago->realizado = true;
        $pago->save();
        $this->log("Pago de inversión #{$id} marcado como realizado");
        return $pago;
    }

    public function getPagoInversion($id)
    {

        return Pago_Inversion::findOrFail($id);
    }
}
