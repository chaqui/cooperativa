<?php

namespace App\Services;

use App\Constants\EstadoPrestamo;
use App\Constants\FrecuenciaPago;
use App\Constants\InicialesCodigo;
use App\EstadosPrestamo\ControladorEstado;
use App\Models\Prestamo_Hipotecario;
use App\Traits\Calculos;
use Illuminate\Support\Facades\DB;

use App\Services\CuotaHipotecaService;
use App\Services\ClientService;
use App\Services\PropiedadService;
use App\Services\CatologoService;
use App\Services\UserService;
use App\Services\SimpleExcelService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;
use App\Traits\ErrorHandler;


class PrestamoService extends CodigoService
{
    use ErrorHandler;
    use Calculos;
    protected $controladorEstado;

    protected $clientService;

    protected $propiedadService;

    protected $catalogoService;

    protected $userService;

    protected  $cuotaHipotecaService;

    protected $prestamoExistenteService;

    protected  PrestamoArchivoService $prestamoArchivoService;

    private string $cancelacionPorPagoTotal = '24';

    public function __construct(
        ControladorEstado $controladorEstado,
        ClientService $clientService,
        PropiedadService $propiedadService,
        CatologoService $catalogoService,
        UserService $userService,
        CuotaHipotecaService $cuotaHipotecaService,
        PrestamoExistenService $prestamoExistenteService,
        PrestamoArchivoService $prestamoArchivoService
    ) {
        $this->controladorEstado = $controladorEstado;
        $this->clientService = $clientService;
        $this->propiedadService = $propiedadService;
        $this->catalogoService = $catalogoService;
        $this->userService = $userService;
        $this->cuotaHipotecaService = $cuotaHipotecaService;
        $this->prestamoExistenteService = $prestamoExistenteService;
        $this->prestamoArchivoService = $prestamoArchivoService;

        parent::__construct(InicialesCodigo::$Prestamo_Hipotecario);
    }

    /**
     * Crea un nuevo préstamo hipotecario
     *
     * @param array $data Datos necesarios para crear el préstamo
     * @return Prestamo_Hipotecario Instancia del préstamo creado
     * @throws \Exception Si ocurre un error durante el proceso
     */
    public function create($request)
    {
        $data = $request->all();
        $data['existente'] = filter_var($data['existente'], FILTER_VALIDATE_BOOLEAN);

        $this->validarFrecuenciaPago($data);
        $this->validarExcel($data, $request);
        DB::beginTransaction();

        try {
            // Validar datos del cliente
            $this->clientService->getClient($data['dpi_cliente']);
            if ($data['fiador_dpi'] != null) {
                $this->clientService->getClient($data['fiador_dpi']);
            }

            // Validar propiedad
            $this->propiedadService->getPropiedad($data['propiedad_id']);

            // Generar código único para el préstamo
            $data['codigo'] = $this->createCode();

            // Obtener el usuario actual
            $usuario = $this->userService->getUserOfToken();
            $data['id_usuario'] = $usuario->id;

            // Crear el préstamo
            $prestamo = Prestamo_Hipotecario::create($data);

            // Cambiar el estado del préstamo a "CREADO" o si es existente hacer pasar todos los estados necesarios
            if ($prestamo->existente) {
                $this->log("Procesando préstamo existente: {$prestamo->codigo}");
                $this->prestamoExistenteService->procesarPrestamoExistente($prestamo, $data, $request->file('file'));
                $this->verificarYCancelarPrestamoSiCorresponde($prestamo);
            } else {
                $this->log("Estableciendo estado inicial para el préstamo: {$prestamo->codigo}");
                $dataEstado = [
                    'razon' => 'Préstamo creado',
                    'estado' => EstadoPrestamo::$CREADO,
                ];

                $this->controladorEstado->cambiarEstado($prestamo, $dataEstado);
            }
            if ($request->hasFile('file_soporte')) {
                $prestamo->path_archivo = $this->prestamoArchivoService->guardarArchivoPrestamo($request->file('file_soporte'), $prestamo->codigo);
                $prestamo->save();
            }

            DB::commit();


            $this->log("Préstamo creado con éxito: {$prestamo->codigo}");
            return $prestamo;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->manejarError($e, 'create prestamo');
            // Esta línea nunca se alcanzará porque manejarError siempre lanza excepción
            $this->lanzarExcepcionConCodigo("Error inesperado en create");
        }
    }

    private function validarExcel($data, $request)
    {
        if ($data['existente'] && !$request->hasFile('file')) {
            $this->lanzarExcepcionConCodigo("El archivo Excel de depósitos es requerido");
        }
    }

    private function validarFrecuenciaPago($data)
    {
        $frecuenciaPago = $data['frecuencia_pago'];
        $plazo = $this->calcularPlazo($data['plazo'], $data['tipo_plazo']);
        $frecuenciasValidas = [
            FrecuenciaPago::$ANUAL => 12,
            FrecuenciaPago::$MENSUAL => 1,
            FrecuenciaPago::$SEMESTRAL => 6,
            FrecuenciaPago::$TRIMESTRAL => 3,
        ];

        if (!isset($frecuenciasValidas[$frecuenciaPago])) {
            $this->lanzarExcepcionConCodigo("La frecuencia de pago '{$frecuenciaPago}' no es válida");
        }

        if ($plazo % $frecuenciasValidas[$frecuenciaPago] !== 0) {
            $this->lanzarExcepcionConCodigo("El plazo debe ser múltiplo de {$frecuenciasValidas[$frecuenciaPago]} meses");
        }
    }
    /**
     * Filtra los datos del request eliminando campos no permitidos para update
     *
     * @param array $data Datos del request
     * @return array Datos filtrados
     */
    private function filtrarDatosParaUpdate(array $data): array
    {
        // Campos que NO se deben actualizar
        $camposProhibidos = [
            'monto_liquido',
            'id',
            'codigo',
            'created_at',
            'updated_at',
            // Agregar más campos según necesites
        ];

        return collect($data)->except($camposProhibidos)->toArray();
    }

    public function update($id, $data)
    {
        // Usar el método helper para filtrar datos
        $dataFiltrada = $this->filtrarDatosParaUpdate($data);

        $prestamo = Prestamo_Hipotecario::find($id);
        $prestamo->update($dataFiltrada);
        return $prestamo;
    }

    public function delete($id)
    {
        $prestamo = Prestamo_Hipotecario::find($id);
        $prestamo->delete();
    }

    public function get($id)
    {
        $this->log('Buscando prestamo con id: ' . $id);
        $prestamo = Prestamo_Hipotecario::find($id);
        if (!$prestamo) {
            $this->log('Prestamo no encontrado con id: ' . $id);
            $this->lanzarExcepcionConCodigo('Prestamo no encontrado con id: ' . $id);
        }
        $this->log('Prestamo encontrado con id: ' . $prestamo->id);
        return $prestamo;
    }

    public function all()
    {
        return Prestamo_Hipotecario::all();
    }

    public function getPagos($id)
    {
        $prestamoHipotecario = $this->get($id);
        return $prestamoHipotecario->pagos;
    }


    public function getRetirosPendientes()
    {
        $this->log('Iniciando búsqueda de retiros pendientes');
        $prestamos = $this->all();
        $retirosPendientes = collect();
        foreach ($prestamos as $prestamo) {
            if (!$prestamo->retiro) {
                continue;
            }
            $retiro = $prestamo->retiro;

            $retiro->codigo_prestamo = $prestamo->codigo;
            $retiro->nombreCliente = $prestamo->cliente->getFullNameAttribute();
            $retiro->gastosAdministrativos = $prestamo->gastos_administrativos;
            $retiro->gastosFormalidad = $prestamo->gastos_formalidad;


            $retirosPendientes->push($retiro);
        }
        $this->log('Retiros pendientes obtenidos: ' . $retirosPendientes->count());
        return $retirosPendientes;
    }

    public function pagarCuota($id, $data)
    {
        DB::beginTransaction();
        try {
            $prestamo = $this->get(id: $id);
            $cuotaApagar = $prestamo->cuotaActiva();

            if (!$cuotaApagar) {
                $this->logError("No hay cuotas activas para el préstamo: {$prestamo->codigo}");
                $this->lanzarExcepcionConCodigo("No hay cuotas activas para el préstamo {$prestamo->codigo}");
            }
            $this->log('Realizando pago de cuota' . $cuotaApagar->numero_pago_prestamo . ' del prestamo : ' . $prestamo->codigo);
            $cuotaHipotecaService = app(CuotaHipotecaService::class);
            $idDeposito = $cuotaHipotecaService->realizarPago($data, $cuotaApagar->id);

            $this->verificarYCancelarPrestamoSiCorresponde($prestamo);

            DB::commit();
            $this->log('Pago realizado con éxito para la cuota: ' . $cuotaApagar->id);
            return $idDeposito;
        } catch (Exception $e) {
            DB::rollBack();
            $this->log('Error al realizar el pago: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function verificarYCancelarPrestamoSiCorresponde(Prestamo_Hipotecario $prestamo)
    {
        $pagosPendientes = $prestamo->pagos()->where('realizado', false)->count();
        if ($pagosPendientes === 0 && $prestamo->saldoPendienteCapital() === 0 && !$prestamo->estaCancelado()) {
            $dataEstado = [
                'razon' => 'Préstamo creado',
                'estado' => EstadoPrestamo::$CREADO,
                'tipo' => $this->cancelacionPorPagoTotal
            ];

            $this->controladorEstado->cambiarEstado($prestamo, $dataEstado);
        }
    }

    /**
     * Cancela un préstamo hipotecario
     *
     * @param int $id ID del préstamo
     * @param string $motivo Motivo de la cancelación
     * @return Prestamo_Hipotecario Préstamo cancelado
     * @throws \Exception Si ocurre un error durante la cancelación
     */
    public function cancelarPrestamo(int $id, string $motivo, string $tipo): Prestamo_Hipotecario
    {
        DB::beginTransaction();
        try {
            // Obtener el préstamo
            $prestamo = $this->get($id);

            // Validar que el préstamo no esté ya cancelado
            if ($prestamo->estaCancelado()) {
                $this->lanzarExcepcionConCodigo(
                    "El préstamo {$prestamo->codigo} ya está cancelado desde {$prestamo->getFechaCancelacionFormateada()}"
                );
            }

            // Validar que el motivo no esté vacío
            if (empty(trim($motivo))) {
                $this->lanzarExcepcionConCodigo("El motivo de cancelación es requerido");
            }

            // Validar longitud del motivo
            if (strlen($motivo) > 500) {
                $this->lanzarExcepcionConCodigo("El motivo de cancelación no puede exceder 500 caracteres");
            }

            $this->log("Iniciando cancelación del préstamo {$prestamo->codigo}. Motivo: {$motivo}");

            // Actualizar el préstamo con los datos de cancelación
            $prestamo->motivo_cancelacion = $motivo;
            $prestamo->fecha_cancelacion = now();

            // Cambiar estado a cancelado (asumiendo que existe el estado 6 para cancelado)
            // Si no existe, se puede mantener el estado actual
            try {
                $estadoCancelado = EstadoPrestamo::$CANCELADO ?? 6;
                $prestamo->estado_id = $estadoCancelado;
                $this->log("Estado del préstamo actualizado a cancelado (estado: {$estadoCancelado})");
            } catch (\Exception $e) {
                $this->log("No se pudo cambiar el estado, manteniendo estado actual: " . $e->getMessage());
            }

            // Guardar cambios
            if (!$prestamo->save()) {
                $this->lanzarExcepcionConCodigo("Error al guardar la cancelación del préstamo");
            }

            // Registrar en el historial de estados si existe la funcionalidad
            try {
                $this->controladorEstado->cambiarEstado($prestamo, $estadoCancelado ?? $prestamo->estado_id);
            } catch (\Exception $e) {
                $this->log("No se pudo registrar en historial de estados: " . $e->getMessage());
            }

            DB::commit();

            $this->log("Préstamo {$prestamo->codigo} cancelado exitosamente");

            return $prestamo;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError("Error al cancelar el préstamo ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca préstamos con filtros múltiples
     *
     * @param array $filtros Array con los filtros de búsqueda
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function buscarPrestamos(array $filtros = [])
    {
        try {
            $query = Prestamo_Hipotecario::with(['cliente', 'estado', 'propiedad', 'asesor']);

            // Filtro por DPI del cliente
            if (!empty($filtros['dpi_cliente'])) {
                $query->where('dpi_cliente', 'like', '%' . $filtros['dpi_cliente'] . '%');
            }

            // Filtro por ID del préstamo
            if (!empty($filtros['id'])) {
                $query->where('id', $filtros['id']);
            }

            // Filtro por código del préstamo
            if (!empty($filtros['codigo'])) {
                $query->where('codigo', 'like', '%' . $filtros['codigo'] . '%');
            }

            // Filtro por nombre del cliente
            if (!empty($filtros['nombre_cliente'])) {
                $query->whereHas('cliente', function ($q) use ($filtros) {
                    $nombreCompleto = $filtros['nombre_cliente'];
                    $q->where(function ($subQ) use ($nombreCompleto) {
                        // Buscar en nombres y apellidos
                        $subQ->where('nombres', 'like', '%' . $nombreCompleto . '%')
                            ->orWhere('apellidos', 'like', '%' . $nombreCompleto . '%')
                            ->orWhere(DB::raw("CONCAT(nombres, ' ', apellidos)"), 'like', '%' . $nombreCompleto . '%')
                            ->orWhere(DB::raw("CONCAT(nombres, ' ', apellidos)"), 'like', '%' . $nombreCompleto . '%');
                    });
                });
            }

            // Filtro por estado del préstamo
            if (!empty($filtros['estado_id'])) {
                $query->where('estado_id', $filtros['estado_id']);
            }

            // Filtro por rango de fechas de inicio
            if (!empty($filtros['fecha_inicio_desde'])) {
                $query->where('fecha_inicio', '>=', $filtros['fecha_inicio_desde']);
            }

            if (!empty($filtros['fecha_inicio_hasta'])) {
                $query->where('fecha_inicio', '<=', $filtros['fecha_inicio_hasta']);
            }

            // Filtro por monto mínimo y máximo
            if (!empty($filtros['monto_minimo'])) {
                $query->where('monto', '>=', $filtros['monto_minimo']);
            }

            if (!empty($filtros['monto_maximo'])) {
                $query->where('monto', '<=', $filtros['monto_maximo']);
            }

            // Filtro por asesor
            if (!empty($filtros['id_usuario'])) {
                $query->where('id_usuario', $filtros['id_usuario']);
            }

            // Filtro para préstamos cancelados o no cancelados
            if (isset($filtros['cancelado'])) {
                if ($filtros['cancelado'] === true || $filtros['cancelado'] === 'true' || $filtros['cancelado'] === '1') {
                    $query->whereNotNull('fecha_cancelacion');
                } else {
                    $query->whereNull('fecha_cancelacion');
                }
            }

            // Ordenamiento
            $ordenPor = $filtros['orden_por'] ?? 'created_at';
            $direccion = $filtros['direccion'] ?? 'desc';
            $query->orderBy($ordenPor, $direccion);

            // Paginación
            $limite = $filtros['limite'] ?? 50;
            if ($limite > 100) {
                $limite = 100; // Máximo 100 registros por consulta
            }

            $resultado = $query->take($limite)->get();

            $this->log("Búsqueda de préstamos completada. Filtros aplicados: " . json_encode($filtros) . ". Resultados: " . $resultado->count());

            return $resultado;
        } catch (\Exception $e) {
            $this->logError("Error en búsqueda de préstamos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca préstamos con paginación
     *
     * @param array $filtros Array con los filtros de búsqueda
     * @param int $pagina Número de página
     * @param int $porPagina Registros por página
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function buscarPrestamosPaginado(array $filtros = [], int $pagina = 1, int $porPagina = 15)
    {
        try {
            $query = Prestamo_Hipotecario::with(['cliente', 'estado', 'propiedad', 'asesor']);

            // Aplicar los mismos filtros que en buscarPrestamos
            if (!empty($filtros['dpi_cliente'])) {
                $query->where('dpi_cliente', 'like', '%' . $filtros['dpi_cliente'] . '%');
            }

            if (!empty($filtros['id'])) {
                $query->where('id', $filtros['id']);
            }

            if (!empty($filtros['codigo'])) {
                $query->where('codigo', 'like', '%' . $filtros['codigo'] . '%');
            }

            if (!empty($filtros['nombre_cliente'])) {
                $query->whereHas('cliente', function ($q) use ($filtros) {
                    $nombreCompleto = $filtros['nombre_cliente'];
                    $q->where(function ($subQ) use ($nombreCompleto) {
                        $subQ->where('nombres', 'like', '%' . $nombreCompleto . '%')
                            ->orWhere('apellidos', 'like', '%' . $nombreCompleto . '%')
                            ->orWhere(DB::raw("CONCAT(nombres, ' ', segundo_nombre, ' ', apellidos)"), 'like', '%' . $nombreCompleto . '%')
                            ->orWhere(DB::raw("CONCAT(primer_nombre, ' ', apellidos)"), 'like', '%' . $nombreCompleto . '%');
                    });
                });
            }

            if (!empty($filtros['estado_id'])) {
                $query->where('estado_id', $filtros['estado_id']);
            }

            if (!empty($filtros['fecha_inicio_desde'])) {
                $query->where('fecha_inicio', '>=', $filtros['fecha_inicio_desde']);
            }

            if (!empty($filtros['fecha_inicio_hasta'])) {
                $query->where('fecha_inicio', '<=', $filtros['fecha_inicio_hasta']);
            }

            if (!empty($filtros['monto_minimo'])) {
                $query->where('monto', '>=', $filtros['monto_minimo']);
            }

            if (!empty($filtros['monto_maximo'])) {
                $query->where('monto', '<=', $filtros['monto_maximo']);
            }

            if (!empty($filtros['id_usuario'])) {
                $query->where('id_usuario', $filtros['id_usuario']);
            }

            if (isset($filtros['cancelado'])) {
                if ($filtros['cancelado'] === true || $filtros['cancelado'] === 'true' || $filtros['cancelado'] === '1') {
                    $query->whereNotNull('fecha_cancelacion');
                } else {
                    $query->whereNull('fecha_cancelacion');
                }
            }

            // Ordenamiento
            $ordenPor = $filtros['orden_por'] ?? 'created_at';
            $direccion = $filtros['direccion'] ?? 'desc';
            $query->orderBy($ordenPor, $direccion);

            // Limitar registros por página
            if ($porPagina > 100) {
                $porPagina = 100;
            }

            $resultado = $query->paginate($porPagina, ['*'], 'page', $pagina);

            $this->log("Búsqueda paginada de préstamos completada. Página: {$pagina}, Por página: {$porPagina}, Total: " . $resultado->total());

            return $resultado;
        } catch (\Exception $e) {
            $this->logError("Error en búsqueda paginada de préstamos: " . $e->getMessage());
            throw $e;
        }
    }

    public function actualizarPrestamo($request, $id)
    {
        try {
            // Usar el método helper para filtrar datos
            $dataFiltrada = $this->filtrarDatosParaUpdate($request->all());

            $prestamo = Prestamo_Hipotecario::find($id);
            if (!$prestamo) {
                $this->lanzarExcepcionConCodigo("Préstamo no encontrado con id: " . $id);
            }
            if ($prestamo->estado_id != EstadoPrestamo::$RECHAZADO) {
                $this->lanzarExcepcionConCodigo("No se puede actualizar un préstamo que no está en estado RECHAZADO");
            }
            $prestamo->update($dataFiltrada);
            $this->log("Préstamo actualizado con éxito: {$prestamo->codigo}");
            $dataEstado = [
                'razon' => 'Corregida la información del préstamo',
                'estado' => EstadoPrestamo::$CREADO,
            ];

            $this->controladorEstado->cambiarEstado($prestamo, $dataEstado);
            return $prestamo;
        } catch (\Exception $e) {
            $this->logError("Error al actualizar el préstamo ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function propiedadAsociada($prestamoId)
    {
        $prestamo = $this->get($prestamoId);
        return $prestamo->propiedadAsociada;
    }
}
