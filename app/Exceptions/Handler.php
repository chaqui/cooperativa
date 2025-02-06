<?php

namespace App\Exceptions;

use App\Traits\Loggable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\JsonResponse;

class Handler extends ExceptionHandler
{
    use Loggable;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register()
    {
        $this->renderable(function (Throwable $e, $request) {
            return $this->handleException($e);
        });
    }
    protected function handleException(Throwable $e): JsonResponse
    {
        $this->logError($e->getMessage());

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json(['error' => 'Server Error', 'message' => $e->getMessage()], 500);
    }
}
