<?php

namespace App\Services;

use App\Traits\Loggable;
use App\Traits\ErrorHandler;

class ArchivoService
{
    use Loggable, ErrorHandler;
    /**
     * Guarda un archivo en el sistema de almacenamiento
     *
     * @param mixed $file Contenido del archivo o instancia de UploadedFile
     * @param string $path Ruta donde se guardará el archivo
     * @param string $fileName Nombre del archivo
     * @return string Ruta completa del archivo guardado
     * @throws \Exception Si ocurre un error al guardar el archivo
     */
    public function guardarArchivo($file, string $path, string $fileName): string
    {
        try {
            $this->log("Guardando archivo en la ruta: {$path} con el nombre: {$fileName}");

            // Determinar si el path es absoluto o relativo
            // Si el path ya contiene storage_path, es absoluto
            if (strpos($path, storage_path('')) !== false) {
                // Path absoluto, usar tal cual
                $fullPath = rtrim($path, '/\\');
            } else {
                // Path relativo, construir ruta completa
                $fullPath = storage_path('app/' . ltrim($path, '/'));
            }

            // Crear directorio si no existe
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0777, true);
                $this->log("Ruta creada: {$fullPath}");
            }

            // Verificar si el archivo ya existe
            $filePath = $fullPath . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($filePath)) {
                $this->log("El archivo ya existe, se sobrescribirá: {$filePath}");
            } else {
                $this->log("El archivo no existe, se creará: {$filePath}");
            }

            // Guardar el archivo
            if (is_string($file)) {
                // Si el archivo es un contenido en forma de string
                file_put_contents($filePath, $file);
            } elseif ($file instanceof \Illuminate\Http\UploadedFile) {
                // Si el archivo es una instancia de UploadedFile
                $file->move($fullPath, $fileName);
            } else {
                $this->lanzarExcepcionConCodigo("El tipo de archivo no es válido. Debe ser un string o una instancia de UploadedFile.");
            }

            $this->log("Archivo guardado exitosamente en: {$filePath}");
            return $filePath;
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'ARC-') === 0) {
                // Si ya es una excepción con código de error, la relanzamos
                throw $e;
            } else {
                // Si es una excepción original, la envolvemos con código de error
                $this->lanzarExcepcionConCodigo("Error al guardar el archivo: " . $e->getMessage(), $e);
            }
        }

        // Esta línea nunca se ejecutará, pero satisface al analizador estático
        return '';
    }

    /**
     * Obtiene el contenido de un archivo
     *
     * @param string $path Ruta del archivo
     * @return string Contenido del archivo
     * @throws \Exception Si el archivo no existe
     */
    public function obtenerArchivo($path): string
    {
        $this->log("Obteniendo archivo desde: {$path}");

        if (file_exists($path)) {
            return file_get_contents($path);
        } else {
            $this->lanzarExcepcionConCodigo("El archivo no existe en la ruta especificada: {$path}");
        }

        // Esta línea nunca se ejecutará, pero satisface al analizador estático
        return '';
    }
}
