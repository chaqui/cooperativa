<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FotografiaService;
use Illuminate\Support\Facades\Storage;

class FotografiaController extends Controller
{
    private $fotografiaService;

    public function __construct(FotografiaService $fotografiaService)
    {
        $this->fotografiaService = $fotografiaService;
    }

    public function deleteFotografia(Request $request)
    {
        $path = $request->input('path');
        $this->fotografiaService->deleteFotografia($path);
        return response()->json(['message' => 'Fotografia deleted successfully'], 204);
    }

    public function getFotografia(Request $request)
    {
        $path = $request->input('path');
        $file = $this->fotografiaService->getFotografia($path);
        $extension = explode('.', $path)[1];

        $mimeType = 'image/' . $extension;

        return response($file, 200)->header('Content-Type', $mimeType);
    }
}
