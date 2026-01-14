<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     * Sanitiza los inputs para prevenir XSS y otros ataques
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Remover tags HTML peligrosos pero permitir algunos seguros
                $value = strip_tags($value, '<p><br><strong><em><u><a>');

                // Limpiar espacios en blanco extra
                $value = trim($value);

                // Convertir caracteres especiales a entidades HTML
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
