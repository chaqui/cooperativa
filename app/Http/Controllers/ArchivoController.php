<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ArchivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ArchivoController extends Controller
{
    protected $archivoService;

    public function __construct(ArchivoService $archivoService)
    {
        $this->archivoService = $archivoService;
    }

    /**
     * Obtiene un archivo desde el almacenamiento.
     * @param Request $request que contiene el path del archivo.
     * @return JsonResponse|Response el contenido del archivo o un mensaje de error.
     * @throws \Exception
     */
    public function obtenerArchivo(Request $request): JsonResponse|Response
    {
        $path = $request->query('path');

        try {
            $contenido = $this->archivoService->obtenerArchivo($path);
             $extension = explode('.', $path)[1];
             $mimeType = 'application/' . $extension;
             return response($contenido, 200)->header('Content-Type', $mimeType);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener el archivo: ' . $e->getMessage()], 500);
        }
    }
}
