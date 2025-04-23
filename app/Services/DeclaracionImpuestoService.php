<?

namespace  App\Services;

use App\Models\Declaracion_Impuesto;
use App\Services\TipoImpuestoService;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;

class DeclaracionImpuestoService
{
    use Loggable;

    private TipoImpuestoService $tipoImpuestoService;

    public function __construct(TipoImpuestoService $tipoImpuestoService)
    {
        $this->tipoImpuestoService = $tipoImpuestoService;
    }

    /**
     * Metodo para crear una nueva declaración de impuesto
     * @param mixed $data Datos necesarios para crear la declaración:
     *        - id_tipo_impuesto: (int) ID del tipo de impuesto
     *        - fecha_inicio: (string) Fecha de inicio del período (formato Y-m-d)
     *        - fecha_fin: (string) Fecha de fin del período (formato Y-m-d)
     * @throws \Exception
     * @return Declaracion_Impuesto
     */
    public function createDeclaracionImpuesto($data)
    {

        $this->validarDeclaracionImpuesto($data);

        DB::beginTransaction();
        try {
            $this->log("Creando declaración de impuesto con datos: " . json_encode($data));
            $declaracionImpuesto = Declaracion_Impuesto::generateDeclaracion($data);
            $declaracionImpuesto->save();
            DB::commit();
            $this->log("Declaración de impuesto creada con ID: " . $declaracionImpuesto->id);
            return $declaracionImpuesto;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError("Error al crear declaración de impuesto: " . $e->getMessage());
            throw new \Exception('Error al crear la declaración de impuesto: ' . $e->getMessage());
        }
    }

    /**
     * Metodo para validar los datos de la declaración de impuesto
     * @param mixed $data Datos de la declaración de impuesto
     * @throws \Exception Si ocurre un error de validación
     * @return void
     */
    private function validarDeclaracionImpuesto($data)
    {
        if (!isset($data['id_tipo_impuesto'])) {
            throw new \Exception('El nombre del tipo de impuesto es requerido');
        }

        if (!isset($data['fecha_inicio'])) {
            throw new \Exception('La fecha de inicio es requerida');
        }
        if (!isset($data['fecha_fin'])) {
            throw new \Exception('La fecha de fin es requerida');
        }

        $tipoImpuesto = $this->tipoImpuestoService->getTipoImpuestoById($data['id_tipo_impuesto']);
        if (!$tipoImpuesto) {
            throw new \Exception('Tipo de impuesto no encontrado');
        }

        $declaracionImpuesto = $tipoImpuesto->declaracionImpuestoFecha($data['fecha_inicio']);
        if ($declaracionImpuesto) {
            throw new \Exception('Ya existe una declaración de impuesto para este tipo en el rango de fechas especificado');
        }
        $declaracionImpuesto = $tipoImpuesto->declaracionImpuestoFecha($data['fecha_fin']);
        if ($declaracionImpuesto) {
            throw new \Exception('Ya existe una declaración de impuesto para este tipo en el rango de fechas especificado');
        }
    }

    /**
     * Obtiene las transacciones asociadas a una declaración de impuesto
     *
     * @param int $id ID de la declaración de impuesto
     * @param array $relations Relaciones a cargar (opcional)
     *        Posibles valores: 'tipoImpuesto', 'transaccionable'
     * @return \Illuminate\Database\Eloquent\Collection Colección de transacciones asociadas
     * @throws \Exception Si la declaración de impuesto no existe
     */
    public function getTransacciones(int $id): \Illuminate\Database\Eloquent\Collection
    {
        try {
            $this->log("Buscando transacciones para la declaración de impuesto con ID: {$id}");

            // Obtener la declaración de impuesto con relaciones opcionales
            $declaracionImpuesto = $this->getDeclaracionImpuesto($id);

            $transacciones = $declaracionImpuesto->impuestoTransacciones;

            $this->log("Transacciones encontradas para la declaración de impuesto con ID: {$id}: " . count($transacciones));

            return $transacciones;
        } catch (\Exception $e) {
            $this->logError("Error al obtener transacciones para la declaración de impuesto con ID: {$id} - " . $e->getMessage());
            throw new \Exception("Error al obtener las transacciones de la declaración de impuesto: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Funcion para declarar un impuesto
     * @param mixed $id ID de la declaración de impuesto
     * @param mixed $data Datos necesarios para declarar el impuesto:
     *       - numero_formulario: (string) Número de formulario
     *      - fecha_presentacion: (string) Fecha de presentación (formato Y-m-d)
     * @throws \Exception
     * @return \Illuminate\Database\Eloquent\Collection<int, Declaracion_Impuesto>
     */
    public function declararImpuesto($id, $data)
    {
        $this->validarDeclaracion($data);
        $declaracionImpuesto = $this->getDeclaracionImpuesto($id);
        if ($declaracionImpuesto->fecha_fin > now()) {
            throw new \Exception('No se puede declarar un impuesto en el futuro');
        }
        DB::beginTransaction();
        try {
            $this->log("Declarando impuesto con ID: " . $id);

            $declaracionImpuesto->declarar($data);
            $declaracionImpuesto->save();
            DB::commit();
            $this->log("Declaración de impuesto declarada con ID: " . $id);
            return $declaracionImpuesto;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError("Error al declarar impuesto: " . $e->getMessage());
            throw new \Exception('Error al declarar el impuesto: ' . $e->getMessage());
        }
    }

    /**
     * Obtener una declaración de impuesto por ID
     * @param mixed $id ID de la declaración de impuesto
     * @throws \Exception Excepción si no se encuentra la declaración
     * @return \Illuminate\Database\Eloquent\Collection<int, Declaracion_Impuesto>
     */
    public function getDeclaracionImpuesto($id)
    {
        try {
            $this->log("Buscando declaración de impuesto con ID: {$id}");
            $declaracionImpuesto = Declaracion_Impuesto::find($id);
            if (!$declaracionImpuesto) {
                $this->logError("Declaración de impuesto no encontrada con ID: " . $id);
                throw new \Exception('Declaración de impuesto no encontrada');
            }
            $this->log("Declaración de impuesto encontrada con ID: {$id}");
            return $declaracionImpuesto;
        } catch (\Exception $e) {
            $this->logError("Error al obtener declaración de impuesto con ID: {$id} - " . $e->getMessage());
            throw new \Exception("Error al obtener la declaración de impuesto: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validar los datos de la declaración
     * @param mixed $data
     * @throws \Exception
     * @return void
     */
    private function validarDeclaracion($data)
    {
        if (!isset($data['numero_formulario'])) {
            throw new \Exception('El número de formulario es requerido');
        }
        if (!isset($data['fecha_presentacion'])) {
            throw new \Exception('La fecha de presentación es requerida');
        }
    }

    /**
     * Obtener todas las declaraciones de impuesto
     * @return \Illuminate\Database\Eloquent\Collection<int, Declaracion_Impuesto>
     */
    public function getAllDeclaracionesImpuesto()
    {
        try {
            $this->log("Obteniendo todas las declaraciones de impuesto");
            $declaracionesImpuesto = Declaracion_Impuesto::all();
            $this->log("Total de declaraciones de impuesto encontradas: " . count($declaracionesImpuesto));
            return $declaracionesImpuesto;
        } catch (\Exception $e) {
            $this->logError("Error al obtener todas las declaraciones de impuesto: " . $e->getMessage());
            throw new \Exception('Error al obtener las declaraciones de impuesto: ' . $e->getMessage());
        }
    }
}
