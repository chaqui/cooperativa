<?php

namespace App\Services;

class PrestamoArchivoService
{
    private $archivoService;

    public function __construct(ArchivoService $archivoService)
    {
        $this->archivoService = $archivoService;
    }

    public function guardarArchivoPrestamo($archivo, $codigoPrestamo)
    {
        $path = 'archivos/prestamos/documentacion';
        $fileName = 'prestamo_' . $codigoPrestamo . '.pdf';
        // Usar el servicio de archivo para guardar el archivo
        return $this->archivoService->guardarArchivo($archivo, $path, $fileName);
    }
}
