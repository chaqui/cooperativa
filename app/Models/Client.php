<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Constants\TipoCliente;

class Client extends Model
{
    protected $table = 'clients';
    protected $primaryKey = 'dpi';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    protected $fillable = [
        'dpi',
        'nombres',
        'apellidos',
        'telefono',
        'correo',
        'direccion',
        'ciudad',
        'departamento',
        'estado_civil',
        'genero',
        'nivel_academico',
        'profesion',
        'fecha_nacimiento',
        'etado_cliente',
        'limite_credito',
        'credito_disponible',
        'ingresos_mensuales',
        'egresos_mensuales',
        'capacidad_pago',
        'calificacion',
        'fecha_actualizacion_calificacion',
        'nit',
        'puesto',
        'fechaInicio',
        'tipoCliente',
        'otrosIngresos',
        'numeroPatente',
        'nombreEmpresa',
        'telefonoEmpresa',
        'direccionEmpresa',
    ];

    public function estadoCliente()
    {
        return $this->belongsTo(Estado_Cliente::class, 'etado_cliente');
    }

    public function cuentasBancarias()
    {
        return $this->hasMany(Cuenta_Bancaria::class, 'dpi_cliente');
    }

    public function prestamosHipotecarios()
    {
        return $this->hasMany(Prestamo_Hipotecario::class, 'dpi_cliente');
    }

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'dpi_cliente');
    }

    public function inversiones()
    {
        return $this->hasMany(Inversion::class, 'dpi_cliente');
    }

    public static function generateClienteBasic($data): Client
    {
        $client = new Client();
        $client->dpi = $data['dpi'];
        $client->nombres = $data['nombres'];
        $client->apellidos = $data['apellidos'];
        $client->telefono = $data['telefono'];
        $client->correo = $data['correo'];
        $client->direccion = $data['direccion'];
        $client->ciudad = $data['ciudad'];
        $client->departamento = $data['departamento'];
        $client->estado_civil = $data['estado_civil'];
        $client->genero = $data['genero'];
        $client->nivel_academico = $data['nivel_academico'];
        $client->profesion = $data['profesion'];
        $client->fecha_nacimiento = $data['fecha_nacimiento'];
        $client->ingresos_mensuales = $data['ingresos_mensuales'];
        $client->egresos_mensuales = $data['egresos_mensuales'];
        $client->fechaInicio = $data['fechaInicio'];
        $client->etado_cliente  = 1;
        return $client;
    }

    public static function generateClienteComercial($data)
    {
        $client = Client::generateClienteBasic($data);
        $client->nit = $data['nit'];
        $client->direccionEmpresa = $data['direccionEmpresa'];
        $client->nombreEmpresa = $data['nombreEmpresa'];
        $client->numeroPatente = $data['numeroPatente'];
        $client->telefonoEmpresa = $data['telefonoEmpresa'];
        return $client;
    }

    public static function generateClienteAsalariado($data)
    {
        $client = Client::generateClienteBasic($data);
        $client->puesto = $data['puesto'];
        $client->fechaInicio = $data['fechaInicio'];
        $client->nombreEmpresa = $data['nombreEmpresa'];
        $client->otrosIngresos = $data['otrosIngresos'];
        return $client;
    }

    public static function generateCliente($data)
    {
        if ($data['tipoCliente'] == TipoCliente::$EMPRESARIO) {
            return Client::generateClienteComercial($data);
        } else {
            return Client::generateClienteAsalariado($data);
        }
    }
}
