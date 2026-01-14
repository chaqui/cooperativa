<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientChange;
use Illuminate\Support\Facades\DB;

class ClientChangeService
{
    private  $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    public function logClientChanges(Client $originalClient, Client $updatedClient, $beneficiarios, $referencias)
    {
        $changes = [];
        $fillableAttributes = $originalClient->getFillable();

        foreach ($fillableAttributes as $attribute) {
            $originalValue = $originalClient->$attribute;
            $updatedValue = $updatedClient->$attribute;

            if ($originalValue !== $updatedValue) {
                $changes[$attribute] = [
                    'antes' => $originalValue,
                    'despues' => $updatedValue,
                ];
            }
        }

        if (isset($beneficiarios)) {
            $beneficiariosOriginales = $beneficiarios['anteriores'] ?? [];
            $beneficiariosActuales = $beneficiarios['actuales'] ?? [];

            // Verificar cambios en beneficiarios (solo campos: nombre, parentezco, porcentaje)
            $cambiosBeneficiarios = $this->detectarCambiosBeneficiarios($beneficiariosOriginales, $beneficiariosActuales);
            if (!empty($cambiosBeneficiarios)) {
                $changes['beneficiarios'] = $cambiosBeneficiarios;
            }
        }
        if (isset($referencias)) {
            $referenciasOriginales = $referencias['anteriores'] ?? [];
            $referenciasActuales = $referencias['actuales'] ?? [];

            // Verificar cambios en referencias (solo campos: nombre, telefono, afinidad)
            $cambiosReferencias = $this->detectarCambiosReferencias($referenciasOriginales, $referenciasActuales);
            if (!empty($cambiosReferencias)) {
                $changes['referencias'] = $cambiosReferencias;
            }
        }

        if (!empty($changes)) {
            $usuario = $this->userService->getUserOfToken();

            ClientChange::create(attributes: [
                'dpi_cliente' => $originalClient->dpi,
                'cambios' => json_encode($changes),
                'usuario_modifico' => $usuario?->username,
            ]);
        }
    }

    public function getReferencesChange($id)
    {
        return ClientChange::getById($id);
    }

    public function getClientChangesByDpi($dpi)
    {
        return ClientChange::where('dpi_cliente', $dpi)->orderBy('created_at', 'desc')->get();
    }

    /**
     * Detectar cambios en beneficiarios comparando solo: nombre, parentezco, porcentaje
     */
    private function detectarCambiosBeneficiarios(array $anteriores, array $actuales): array
    {
        $cambios = [];
        $camposComparar = ['nombre', 'parentezco', 'porcentaje'];

        // Indexar por ID para facilitar comparación
        $anterioresPorId = collect($anteriores)->keyBy('id')->toArray();
        $actualesPorId = collect($actuales)->keyBy('id')->toArray();

        // Detectar modificados y eliminados
        foreach ($anterioresPorId as $id => $anterior) {
            if (isset($actualesPorId[$id])) {
                // Existe en ambos - verificar cambios en campos específicos
                $actual = $actualesPorId[$id];
                $cambiosCampos = [];

                foreach ($camposComparar as $campo) {
                    $valorAnterior = $anterior[$campo] ?? null;
                    $valorActual = $actual[$campo] ?? null;

                    // Normalizar valores vacíos para comparación
                    $anteriorNormalizado = $this->normalizarValor($valorAnterior);
                    $actualNormalizado = $this->normalizarValor($valorActual);

                    if ($anteriorNormalizado !== $actualNormalizado) {
                        $cambiosCampos[$campo] = [
                            'antes' => $valorAnterior,
                            'despues' => $valorActual
                        ];
                    }
                }

                if (!empty($cambiosCampos)) {
                    $cambios['modificados'][] = [
                        'id' => $id,
                        'nombre' => $actual['nombre'] ?? $anterior['nombre'] ?? 'Sin nombre',
                        'cambios' => $cambiosCampos
                    ];
                }
            } else {
                // No existe en actuales - fue eliminado
                $cambios['eliminados'][] = [
                    'id' => $id,
                    'nombre' => $anterior['nombre'] ?? 'Sin nombre',
                    'parentezco' => $anterior['parentezco'] ?? null,
                    'porcentaje' => $anterior['porcentaje'] ?? null
                ];
            }
        }

        // Detectar nuevos
        foreach ($actualesPorId as $id => $actual) {
            if (!isset($anterioresPorId[$id])) {
                $cambios['agregados'][] = [
                    'id' => $id,
                    'nombre' => $actual['nombre'] ?? 'Sin nombre',
                    'parentezco' => $actual['parentezco'] ?? null,
                    'porcentaje' => $actual['porcentaje'] ?? null
                ];
            }
        }

        return $cambios;
    }

    /**
     * Detectar cambios en referencias comparando solo: nombre, telefono, afinidad
     */
    private function detectarCambiosReferencias(array $anteriores, array $actuales): array
    {
        $cambios = [];
        $camposComparar = ['nombre', 'telefono', 'afinidad'];

        // Indexar por ID para facilitar comparación
        $anterioresPorId = collect($anteriores)->keyBy('id')->toArray();
        $actualesPorId = collect($actuales)->keyBy('id')->toArray();

        // Detectar modificados y eliminados
        foreach ($anterioresPorId as $id => $anterior) {
            if (isset($actualesPorId[$id])) {
                // Existe en ambos - verificar cambios en campos específicos
                $actual = $actualesPorId[$id];
                $cambiosCampos = [];

                foreach ($camposComparar as $campo) {
                    $valorAnterior = $anterior[$campo] ?? null;
                    $valorActual = $actual[$campo] ?? null;

                    // Normalizar valores vacíos para comparación
                    $anteriorNormalizado = $this->normalizarValor($valorAnterior);
                    $actualNormalizado = $this->normalizarValor($valorActual);

                    if ($anteriorNormalizado !== $actualNormalizado) {
                        $cambiosCampos[$campo] = [
                            'antes' => $valorAnterior,
                            'despues' => $valorActual
                        ];
                    }
                }

                if (!empty($cambiosCampos)) {
                    $cambios['modificados'][] = [
                        'id' => $id,
                        'nombre' => $actual['nombre'] ?? $anterior['nombre'] ?? 'Sin nombre',
                        'cambios' => $cambiosCampos
                    ];
                }
            } else {
                // No existe en actuales - fue eliminado
                $cambios['eliminados'][] = [
                    'id' => $id,
                    'nombre' => $anterior['nombre'] ?? 'Sin nombre',
                    'telefono' => $anterior['telefono'] ?? null,
                    'afinidad' => $anterior['afinidad'] ?? null
                ];
            }
        }

        // Detectar nuevos
        foreach ($actualesPorId as $id => $actual) {
            if (!isset($anterioresPorId[$id])) {
                $cambios['agregados'][] = [
                    'id' => $id,
                    'nombre' => $actual['nombre'] ?? 'Sin nombre',
                    'telefono' => $actual['telefono'] ?? null,
                    'afinidad' => $actual['afinidad'] ?? null
                ];
            }
        }

        return $cambios;
    }

    /**
     * Normalizar valor para comparación
     * Convierte null, string vacío y espacios en blanco a null
     */
    private function normalizarValor($valor)
    {
        if ($valor === null || $valor === '' || (is_string($valor) && trim($valor) === '')) {
            return null;
        }
        return is_string($valor) ? trim($valor) : $valor;
    }
}
