<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Constants\TipoCliente;
use Illuminate\Support\Facades\Log;

class Client extends Model
{
    protected $table = 'clients';
    protected $primaryKey = 'dpi';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    protected $fillable = [
        'dpi', //
        'nombres', //
        'apellidos', //
        'telefono', //
        'correo', //
        'direccion', //
        'ciudad', //
        'departamento', //
        'estado_civil', //
        'genero', //
        'nivel_academico', //
        'profesion', //
        'fecha_nacimiento', //
        'etado_cliente',  //-
        'limite_credito', //-
        'credito_disponible', //-
        'ingresos_mensuales', //
        'egresos_mensuales', //
        'capacidad_pago', //=
        'calificacion', //-
        'fecha_actualizacion_calificacion', //-
        'nit', //
        'puesto', //
        'fechaInicio', //
        'tipoCliente', //-
        'otrosIngresos', //
        'razon_otros_ingresos', //
        'numeroPatente', //
        'nombreEmpresa', //
        'telefonoEmpresa', //
        'direccionEmpresa', //
        'path', //
        'codigo',
        'conyuge',
        'cargas_familiares',
        'integrantes_nucleo_familiar',
        'tipo_vivienda',
        'estabilidad_domiciliaria',
        'nacionalidad', // Nuevo campo para nacionalidad
        'carga_familiar' // Nuevo campo para carga familiar

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

    public function inversiones()
    {
        return $this->hasMany(Inversion::class, 'dpi_cliente');
    }

    public function propiedades()
    {
        return $this->hasMany(Propiedad::class, 'dpi_cliente');
    }

    public static function generateClienteBasic($data, $client): Client
    {
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
        $client->ingresos_mensuales = $data['ingresosMensuales'];
        $client->egresos_mensuales = $data['egresosMensuales'];
        $client->fechaInicio = $data['fechaInicio'];
        $client->tipoCliente = $data['tipoCliente'];
        $client->path = $data['path'];
        $client->codigo = $data['codigo'];
        if ($data['estado_civil'] == '18') {
            $client->conyuge = $data['conyuge'];
            $client->cargas_familiares = $data['cargas_familiares'];
            $client->integrantes_nucleo_familiar = $data['integrantes_nucleo_familiar'];
            $client->estabilidad_domiciliaria = $data['estabilidad_domiciliaria'];
            $client->tipo_vivienda = $data['tipo_vivienda'];
        }
        $client->etado_cliente  = 1;
        return $client;
    }

    public static function generateClienteComercial($data, $client)
    {
        $client = Client::generateClienteBasic($data, $client);
        $client->nit = $data['nit'];
        $client->direccionEmpresa = $data['direccionEmpresa'];
        $client->nombreEmpresa = $data['nombreEmpresa'];
        $client->numeroPatente = $data['patente'];
        $client->telefonoEmpresa = $data['telefonoEmpresa'];
        return $client;
    }

    public static function generateClienteAsalariado($data, $client)
    {
        $client = Client::generateClienteBasic($data, $client);
        $client->puesto = $data['puesto'];
        $client->fechaInicio = $data['fechaInicio'];
        $client->nombreEmpresa = $data['nombreEmpresa'];
        $client->direccionEmpresa = $data['direccionEmpresa'];
        $client->telefonoEmpresa = $data['telefonoEmpresa'];
        $client->otrosIngresos = $data['otrosIngresos'];
        $client->razon_otros_ingresos = $data['razon_otros_ingresos'] ?? null;
        return $client;
    }

    public static function generateCliente($data)
    {
        $client = new Client();
        if ($data['tipoCliente'] == TipoCliente::$EMPRESARIO) {
            return Client::generateClienteComercial($data, $client);
        } else {
            return Client::generateClienteAsalariado($data, $client);
        }
    }

    public function references()
    {
        return $this->hasMany(Reference::class, 'dpi_cliente', 'dpi');
    }

    public function getFullNameAttribute()
    {
        return $this->nombres . ' ' . $this->apellidos;
    }

    public static function updateData($data, $client)
    {
        if ($data['tipoCliente'] == TipoCliente::$EMPRESARIO) {
            return Client::generateClienteComercial($data, $client);
        } else {
            return Client::generateClienteAsalariado($data, $client);
        }
    }

    public function getCuotasPendientes()
    {
        $prestamos = $this->prestamosHipotecarios;
        $cuotas = collect();
        foreach ($prestamos as $prestamo) {
            $cuotasPendientes = $prestamo->getCuotasPendientes();
            if ($cuotasPendientes->isNotEmpty()) {
                $cuotas = $cuotas->merge($cuotasPendientes);
            }
        }
        return $cuotas;
    }

    /**
     *
     * Get the beneficiarios associated with the client.
     * @return HasMany<Beneficiario, Client>
     */
    public function beneficiarios()
    {
        return $this->hasMany(Beneficiario::class, 'dpi_cliente');
    }
}
