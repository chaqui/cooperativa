<?



namespace App\Services;


use App\Models\TipoImpuesto;

use App\Models\Declaracion_Impuesto;
use App\Traits\Loggable;

class TipoImpuestoService
{
    use Loggable;

    /**
     * Obtiene un tipo de impuesto por su nombre.
     * @param string $nombre Nombre del tipo de impuesto.
     * @throws \Exception Si ocurre un error al buscar el tipo de impuesto.
     * @return TipoImpuesto|null El tipo de impuesto encontrado o null si no se encuentra.
     */
    public function getTipoImpustoByNombre($nombre)
    {
        try {
            $this->log("Buscando tipo de impuesto con nombre: " . json_encode($nombre));

            $query = TipoImpuesto::query();


            return $query->where('nombre', $nombre)->first();
        } catch (\Exception $e) {
            $this->logError("Error al obtener tipo de impuesto: " . $e->getMessage());
            throw new \Exception("Error al obtener el tipo de impuesto: " . $e->getMessage(), 0, $e);
        }
    }
    public function getTipoImpuestoById($id)
    {
        // Validar que el ID sea válido
        if (empty($id) || !is_numeric($id) || $id <= 0) {
            $this->logError("ID de tipo de impuesto inválido: {$id}");
            throw new \InvalidArgumentException("El ID del tipo de impuesto debe ser un valor numérico positivo");
        }

        try {
            $this->log("Buscando tipo de impuesto con ID: {$id}");


            // Construir consulta
            $query = TipoImpuesto::query();

            // Ejecutar consulta
            $tipoImpuesto = $query->findOrFail($id);

            $this->log("Tipo de impuesto encontrado: {$tipoImpuesto->nombre} (ID: {$id})");

            return $tipoImpuesto;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->logError("Tipo de impuesto no encontrado con ID: {$id}");
            throw $e;
        } catch (\Exception $e) {
            $this->logError("Error al obtener tipo de impuesto #{$id}: " . $e->getMessage());
            throw new \Exception("Error al obtener el tipo de impuesto: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene todos los tipos de impuestos.
     * @throws \Exception
     * @return \Illuminate\Database\Eloquent\Collection<int, TipoImpuesto>
     */
    public function getTiposImpuestos()
    {
        try {
            $this->log("Buscando todos los tipos de impuestos");

            // Construir consulta
            $query = TipoImpuesto::query();

            // Ejecutar consulta
            $tiposImpuestos = $query->get();

            $this->log("Tipos de impuestos encontrados: " . count($tiposImpuestos));

            return $tiposImpuestos;
        } catch (\Exception $e) {
            $this->logError("Error al obtener tipos de impuestos: " . $e->getMessage());
            throw new \Exception("Error al obtener los tipos de impuestos: " . $e->getMessage(), 0, $e);
        }
    }

    public function getDeclaraciones($id)
    {
        try {
            $this->log("Buscando declaraciones de impuesto con ID: {$id}");

            $tipoImpuesto = $this->getTipoImpuestoById($id);
            $declaraciones = $tipoImpuesto->declaracionImpuestos;
            if ($declaraciones->isEmpty()) {
                $this->log("No se encontraron declaraciones de impuesto para el tipo con ID: {$id}");
                return [];
            }
            $this->log("Declaraciones de impuesto encontradas para el tipo con ID: {$id}");
            $this->log("Total de declaraciones de impuesto encontradas: " . count($declaraciones));
            return $declaraciones;
        } catch (\Exception $e) {
            $this->logError("Error al obtener declaraciones de impuesto: " . $e->getMessage());
            throw new \Exception("Error al obtener las declaraciones de impuesto: " . $e->getMessage(), 0, $e);
        }
    }
}
