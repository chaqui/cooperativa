<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ErrorHandler
{
    /**
     * Genera un código de error único basado en el nombre de la clase
     */
    private function generarCodigoError(): string
    {
        // Obtener las iniciales de la clase (ej: PrestamoService -> PS)
        $className = class_basename(static::class);
        $initials = $this->extractInitials($className);

        return $initials . '-' . date('YmdHis') . '-' . substr(uniqid(), -6);
    }

    /**
     * Extrae las iniciales de un nombre de clase
     */
    private function extractInitials(string $className): string
    {
        // Remover "Service" del final si existe
        $className = str_replace('Service', '', $className);

        // Dividir por mayúsculas y tomar las iniciales
        preg_match_all('/[A-Z]/', $className, $matches);
        $initials = implode('', $matches[0]);

        // Si no hay iniciales suficientes, usar las primeras 3 letras
        if (strlen($initials) < 2) {
            $initials = strtoupper(substr($className, 0, 3));
        }

        // Limitar a máximo 4 caracteres
        return substr($initials, 0, 4);
    }

    /**
     * Registra el error con código y lanza excepción
     * @throws \Exception Siempre lanza una excepción
     */
    private function lanzarExcepcionConCodigo(string $mensaje, \Exception $excepcionOriginal = null): void
    {
        $codigoError = $this->generarCodigoError();
        $mensajeCompleto = "[{$codigoError}] {$mensaje}";

        // Usar logError si está disponible (del trait Loggable), sino usar log de Laravel
        if (method_exists($this, 'logError')) {
            $this->logError($mensajeCompleto);
            if ($excepcionOriginal) {
                $this->logError("Excepción original: " . $excepcionOriginal->getMessage());
                $this->logError("Trace: " . $excepcionOriginal->getTraceAsString());
            }
        } else {
            Log::error($mensajeCompleto);
            if ($excepcionOriginal) {
                Log::error("Excepción original: " . $excepcionOriginal->getMessage());
                Log::error("Trace: " . $excepcionOriginal->getTraceAsString());
            }
        }

        if ($excepcionOriginal) {
            throw new \Exception($mensajeCompleto, 0, $excepcionOriginal);
        } else {
            throw new \Exception($mensajeCompleto);
        }
    }

    /**
     * Wrapper para manejar errores en try-catch existentes
     */
    private function manejarError(\Exception $e, string $contexto = ''): void
    {
        $prefijo = static::class;
        if (!empty($contexto)) {
            $prefijo .= " ({$contexto})";
        }

        // Verificar si ya tiene código de error
        if (preg_match('/^\[[A-Z]+-\d+-[A-F0-9]+\]/', $e->getMessage())) {
            // Ya tiene código, solo relanzar
            throw $e;
        } else {
            // Agregar código de error
            $this->lanzarExcepcionConCodigo($prefijo . ': ' . $e->getMessage(), $e);
        }
    }

    /**
     * Ejecuta una función con manejo automático de errores
     */
    private function ejecutarConManejadorError(callable $funcion, string $contexto = '')
    {
        try {
            return $funcion();
        } catch (\Exception $e) {
            $this->manejarError($e, $contexto);
        }
    }
}
