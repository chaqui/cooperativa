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

        if (Storage::disk('public')->exists($path)) {
            $file = Storage::disk('public')->get($path);
            $mimeType = 'image/png';

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        return response()->json(['message' => 'File not found'], 404);
    }
}
