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
    public function __construct(
        ControladorEstado $controladorEstado,
        ClientService $clientService,
        PropiedadService $propiedadService,
        CatologoService $catalogoService,
        UserService $userService,
        CuotaHipotecaService $cuotaHipotecaService,
        PrestamoExistenService $prestamoExistenteService
    ) {
        $this->controladorEstado = $controladorEstado;
        $this->clientService = $clientService;
        $this->propiedadService = $propiedadService;
        $this->catalogoService = $catalogoService;
        $this->userService = $userService;
        $this->cuotaHipotecaService = $cuotaHipotecaService;
        $this->prestamoExistenteService = $prestamoExistenteService;

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
            } else {
                $this->log("Estableciendo estado inicial para el préstamo: {$prestamo->codigo}");
                $dataEstado = [
                    'razon' => 'Préstamo creado',
                    'estado' => EstadoPrestamo::$CREADO,
                ];

                $this->controladorEstado->cambiarEstado($prestamo, $dataEstado);
            }
            $prestamo->save();
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
    public function update($id, $data)
    {
        $prestamo = Prestamo_Hipotecario::find($id);
        $prestamo->update($data);
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
            $prestamo = $this->get($id);
            $cuotaApagar = $prestamo->cuotaActiva();

            if (!$cuotaApagar) {
                $this->logError("No hay cuotas activas para el préstamo: {$prestamo->codigo}");
                $this->lanzarExcepcionConCodigo("No hay cuotas activas para el préstamo {$prestamo->codigo}");
            }
            $this->log('Realizando pago de cuota' . $cuotaApagar->numero_pago_prestamo . ' del prestamo : ' . $prestamo->codigo);
            $cuotaHipotecaService = app(CuotaHipotecaService::class);
            $cuotaHipotecaService->realizarPago($data, $cuotaApagar->id);

            DB::commit();
            $this->log('Pago realizado con éxito para la cuota: ' . $cuotaApagar->id);
        } catch (Exception $e) {
            DB::rollBack();
            $this->log('Error al realizar el pago: ' . $e->getMessage());
            throw $e;
        }
    }
}
