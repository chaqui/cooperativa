<?php

namespace App\Http\Middleware;

use App\Traits\Loggable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogErrors
{
    use Loggable;
    public function handle(Request $request, Closure $next)
    {
        try {
            $this->log('Request URL: ' . $request->fullUrl());
            return $next($request);
        } catch (\Throwable $e) {
            $this->logError('Error: ');
            // Log detallado
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'inputs' => $request->all()
            ];

            // Guardar en archivo directamente
            file_put_contents(
                storage_path('logs/error-' . date('Y-m-d') . '.log'),
                date('Y-m-d H:i:s') . ' - ' . json_encode($errorDetails) . "\n",
                FILE_APPEND
            );

            return response()->json([
                'message' => 'Ha ocurrido un error interno.',
                'details' => $errorDetails // Devolver todos los detalles en desarrollo
            ], 500);
        }
    }
}
