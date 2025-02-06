<?php

namespace App\Http\Middleware;

use App\Traits\Authorizable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckRole
{
    use Authorizable;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $roles)
    {
        try {

            if (!$this->authorizeRol(explode('|', $roles))) {
                throw new HttpResponseException(response()->json([
                    'message' => 'This action is unauthorized.'
                ], 403));
            }
        } catch (\Exception $e) {
            throw new HttpResponseException(response()->json([
                'message' => 'Token is invalid or missing.'
            ], 401));
        }

        return $next($request);
    }
}
