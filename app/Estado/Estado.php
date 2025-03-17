<?php

namespace App\Estado;

abstract class Estado
{
    protected $estadoFin;
    protected $estadoInicio;

    public function __construct($estadoInicio, $estadoFin)
    {
        $this->estadoInicio = $estadoInicio;
        $this->estadoFin = $estadoFin;
    }

}
