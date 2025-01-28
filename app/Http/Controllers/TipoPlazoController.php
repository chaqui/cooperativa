<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\TipoPlazoService;

use App\Http\Resources\TipoPlazo as TipoPlazoResource;
use Filament\Notifications\Collection;

class TipoPlazoController extends Controller
{
    private $tipoPlazoService;

    public function __construct(TipoPlazoService $tipoPlazoService)
    {
        $this->tipoPlazoService = $tipoPlazoService;
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection The collection of types.
     */
    public function index()
    {
        $tipoPlazos = $this->tipoPlazoService->getTipoPlazos();
        return TipoPlazoResource::collection($tipoPlazos);
    }

    /**
     * Show the type for your id.
     * @param string $id The id of the type.
     * @return TipoPlazoResource The type resource.
     */
    public function show(string $id)
    {
        $tipoPlazo = $this->tipoPlazoService->getTipoPlazo($id);
        return new TipoPlazoResource($tipoPlazo);
    }
}
