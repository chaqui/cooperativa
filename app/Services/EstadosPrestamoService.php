<?

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Prestamo_Hipotecario;
use App\EstadosPrestamo\ControladorEstado;

class EstadosPrestamoService extends PrestamoService
{
    public function __construct(
        ControladorEstado $controladorEstado,
        ClientService $clientService,
        PropiedadService $propiedadService,
        CatologoService $catalogoService,
        UserService $userService,
        CuotaHipotecaService $cuotaHipotecaService,
        PrestamoExistenService $prestamoExistenteService
    ) {
        parent::__construct(
            $controladorEstado,
            $clientService,
            $propiedadService,
            $catalogoService,
            $userService,
            $cuotaHipotecaService,
            $prestamoExistenteService
        );
    }

    /**
     * Función para cambiar el estado de un préstamo hipotecario
     * @param mixed $id identificador del préstamo
     * @param mixed $data datos necesarios para cambiar el estado
     * @return void 
     */
    public function cambiarEstado($id, $data)
    {
        DB::beginTransaction();
        $prestamo = $this->get($id);
        $this->log('Cambiando estado del prestamo: ' . $prestamo->codigo);
        $this->controladorEstado->cambiarEstado($prestamo, $data);
        DB::commit();
    }

    /**
     * Obtiene todos los préstamos hipotecarios por estado
     * @param mixed $estado identificador del estado
     * @return \Illuminate\Database\Eloquent\Collection<int, Prestamo_Hipotecario>
     */
    public function getPrestamosByEstado($estado)
    {
        return Prestamo_Hipotecario::where('estado_id', $estado)->get();
    }

    /**
     * Obtiene el historial de estados de un préstamo hipotecario
     * @param mixed $id 
     */
    public function getHistorial($id)
    {
        $prestamo = $this->get($id);
        return $prestamo->historial;
    }
}
