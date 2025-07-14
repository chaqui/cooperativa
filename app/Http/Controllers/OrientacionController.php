<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrientacionService;
use App\Http\Resources\Orientacion as OrientacionResource;

class OrientacionController extends Controller
{
    private $orientacionService;

    public function __construct(OrientacionResource $orientacionService)
    {
        $this->orientacionService = $orientacionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orientations = $this->orientacionService->getOrientations();
        return OrientacionResource::collection($orientations);
    }
}
