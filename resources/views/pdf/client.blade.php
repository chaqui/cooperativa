@php
    use Carbon\Carbon;
    Carbon::setLocale('es');
    function base64Image($path)
    {
        $fullPath = storage_path("app/public/" . $path);
        $type = pathinfo($fullPath, PATHINFO_EXTENSION);
        $data = file_get_contents($fullPath);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    function hasReferences($references)
    {
        if (empty($references)) {
            return false;
        }

        if (is_array($references)) {
            return count($references) > 0;
        }

        if (is_object($references) && method_exists($references, 'count')) {
            return $references->count() > 0;
        }

        return false;
    }

    function hasBeneficiarios($beneficiarios)
    {
        if (empty($beneficiarios)) {
            return false;
        }

        if (is_array($beneficiarios)) {
            return count($beneficiarios) > 0;
        }

        if (is_object($beneficiarios) && method_exists($beneficiarios, 'count')) {
            return $beneficiarios->count() > 0;
        }

        return false;
    }
@endphp
<!DOCTYPE html>
<html>

<head>
    <title>Client PDF</title>
    <style>
        /* Agrega tus estilos aquí */
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        .content {
            margin: 5px;
            border: 1px solid black;
            padding: 5px;
        }

        .logo {
            width: 100px;
            height: auto;
        }

        .signature {
            text-align: center;
            margin-top: 50px;
        }

        .client-photo {
            width: 150px;
            height: auto;
            margin-right: 10px;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ base64Image('images/logoNegro.png') }}" alt="Logo" class="logo">
        <h1>Información del Cliente</h1>
        @if($client->path)
            <img src="{{ base64Image($client->path) }}" alt="Fotografía del Cliente" class="client-photo">
        @endif
        <h2>
           ({{ $client->codigo }}) {{ $client->nombres }} {{ $client->apellidos }}
        </h2>
    </div>
    <table class="content">
        <tr>
            @if($client->nombres || $client->apellidos)
                <td><strong>Nombre:</strong></td>
                <td>{{ $client->nombres }} {{ $client->apellidos }}</td>
            @endif
            @if($client->dpi)
                <td><strong>CUI:</strong></td>
                <td> {{ substr($client->dpi, 0, 4) }} {{ substr($client->dpi, 4, 5) }} {{ substr($client->dpi, 9, 4) }}</td>
            @endif
            @if($client->nombreTipoCliente)
                <td><strong>Tipo De Cliente:</strong></td>
                <td>{{ $client->nombreTipoCliente}}</td>
            @endif
        </tr>
    </table>
    <table class="content">
        <tr>
            @if($client->correo)
                <td><strong>Email:</strong></td>
                <td colspan="2">{{ $client->correo }}</td>
            @endif
            @if($client->telefono)
                <td><strong>Telefono:</strong></td>
                <td colspan="2">{{ $client->telefono }}</td>
            @endif
        </tr>
        @if($client->direccion)
            <tr>
                <td><strong>Direccion:</strong></td>
                <td colspan="5">{{ $client->direccion }}</td>
            </tr>
        @endif
        <tr>
            @if($client->nombreMunicipio)
                <td><strong>Municipio:</strong></td>
                <td colspan="2">{{ $client->nombreMunicipio }}</td>
            @endif
            @if($client->nombreDepartamento)
                <td><strong>Departamento:</strong></td>
                <td colspan="2">{{ $client->nombreDepartamento }}</td>
            @endif
        </tr>
        @if($client->nacionalidad)
            <tr>
                <td><strong>Nacionalidad:</strong></td>
                <td colspan="5">{{ $client->nacionalidad }}</td>
            </tr>
        @endif
        <tr>
            @if($client->fecha_nacimiento)
                <td><strong>Fecha de Nacimiento:</strong></td>
                <td>{{ Carbon::parse($client->fecha_nacimiento)->translatedFormat('d \d\e F \d\e Y') }}</td>
            @endif
            @if($client->genero)
                <td><strong>Genero:</strong></td>
                <td>{{ $client->genero }}</td>
            @endif
            @if($client->estadoCivil)
                <td><strong>Estado Civil:</strong></td>
                <td>{{ $client->estadoCivil }}(a)</td>
            @endif
        </tr>
    </table>
    @if($client->nivel_academico || $client->profesion)
        <table class="content">
            <tr>
                @if($client->nivel_academico)
                    <td><strong>Nivel Academico:</strong></td>
                    <td>{{ $client->nivel_academico }}</td>
                @endif
                @if($client->profesion)
                    <td><strong>Profesion:</strong></td>
                    <td>{{ $client->profesion }}</td>
                @endif
            </tr>
        </table>
    @endif
    @if($client->nombreEmpresa || $client->fechaInicio || $client->direccionEmpresa || $client->telefonoEmpresa || $client->numeroPatente || $client->nit || $client->puesto || $client->ingresos_mensuales || $client->egresos_mensuales || $client->otrosIngresos || $client->razon_otros_ingresos)
        <table class="content">
            @if($client->nombreEmpresa || $client->fechaInicio)
                <tr>
                    @if($client->nombreEmpresa)
                        <td><strong>Nombre de la Empresa:</strong></td>
                        <td>{{ $client->nombreEmpresa }}</td>
                    @endif
                    @if($client->fechaInicio)
                        <td><strong>Fecha de Inicio:</strong></td>
                        <td>{{ Carbon::parse($client->fechaInicio)->translatedFormat('d \d\e F \d\e Y') }}</td>
                    @endif
                </tr>
            @endif
            @if($client->direccionEmpresa)
                <tr>
                    <td><strong>Direccion de la Empresa:</strong></td>
                    <td colspan="3">{{ $client->direccionEmpresa }}</td>
                </tr>
            @endif
            @if($client->telefonoEmpresa)
                <tr>
                    <td><strong>Telefono de la Empresa:</strong></td>
                    <td colspan="3">{{ $client->telefonoEmpresa }}</td>
                </tr>
            @endif
            @if ($client->tipoCliente == '390')
                @if($client->numeroPatente || $client->nit)
                    <tr>
                        @if($client->numeroPatente)
                            <td><strong>Número de Patente:</strong></td>
                            <td>{{ $client->numeroPatente }}</td>
                        @endif
                        @if($client->nit)
                            <td><strong>NIT:</strong></td>
                            <td>{{ $client->nit }}</td>
                        @endif
                    </tr>
                @endif
            @else
                @if($client->puesto)
                    <tr>
                        <td><strong>Puesto:</strong></td>
                        <td colspan="3">{{ $client->puesto }}</td>
                    </tr>
                @endif
            @endif
            @if($client->ingresos_mensuales || $client->egresos_mensuales)
                <tr>
                    @if($client->ingresos_mensuales)
                        <td><strong>Ingresos Mensuales:</strong></td>
                        <td>Q. {{ number_format($client->ingresos_mensuales, 2, '.', ',') }}</td>
                    @endif
                    @if($client->egresos_mensuales)
                        <td><strong>Egresos Mensuales:</strong></td>
                        <td>Q. {{ number_format($client->egresos_mensuales, 2, '.', ',')  }}</td>
                    @endif
                </tr>
            @endif
            @if ($client->tipoCliente != '390')
                @if($client->otrosIngresos)
                    <tr>
                        <td><strong>Otros Ingresos:</strong></td>
                        <td colspan="3"> Q. {{ number_format($client->otrosIngresos, 2, '.', ',') }}</td>
                    </tr>
                @endif
                @if($client->razon_otros_ingresos)
                    <tr>
                        <td><strong>Razon Otros Ingresos:</strong></td>
                        <td colspan="3">{{ $client->razon_otros_ingresos }}</td>
                    </tr>
                @endif
            @endif
        </table>
    @endif

    @if ($client->estado_civil == '18' && ($client->conyuge || $client->cargas_familiares || $client->integrantes_nucleo_familiar || $client->tipo_vivienda || $client->estabilidad_domiciliaria))
        <h3>Información Familiar</h3>
        <table class="content">
            @if($client->conyuge)
                <tr>
                    <td><strong>Conyuge:</strong></td>
                    <td>{{ $client->conyuge }}</td>
                </tr>
            @endif
            @if($client->cargas_familiares)
                <tr>
                    <td><strong>Cargas Familiares:</strong></td>
                    <td>{{ $client->cargas_familiares }}</td>
                </tr>
            @endif
            @if($client->integrantes_nucleo_familiar)
                <tr>
                    <td><strong>No. Integrantes nucleo familar</strong></td>
                    <td>{{ $client->integrantes_nucleo_familiar }}</td>
                </tr>
            @endif
            @if($client->tipo_vivienda)
                <tr>
                    <td><strong>La casa donde vive:</strong></td>
                    <td>{{ $client->tipo_vivienda }}</td>
                </tr>
            @endif
            @if($client->estabilidad_domiciliaria)
                <tr>
                    <td><strong>Tiempo de estabilidad domiciliar:</strong></td>
                    <td>{{ $client->estabilidad_domiciliaria }} año(s)</td>
                </tr>
            @endif
        </table>
    @endif

    @if (hasBeneficiarios($client->beneficiarios))
        <h3>Beneficiarios</h3>
        <table class="content">
            <tr>
                <th>Nombre</th>
                <th>Parentesco</th>
                <th>Fecha de Nacimiento</th>
                <th>Porcentaje (%)</th>
            </tr>
            @foreach ($client->beneficiarios as $beneficiario)
                <tr>
                    <td>{{ is_array($beneficiario) ? $beneficiario['nombre'] ?? '' : ($beneficiario->nombre ?? '') }}</td>
                    <td>{{ is_array($beneficiario) ? $beneficiario['parentezco'] ?? '' : ($beneficiario->parentezco ?? '') }}</td>
                    <td>
                        @php
                            $fecha = is_array($beneficiario) ? ($beneficiario['fecha_nacimiento'] ?? null) : ($beneficiario->fecha_nacimiento ?? null);
                        @endphp
                        {{ $fecha ? \Carbon\Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y') : '' }}
                    </td></td>
                    <td style="text-align: center;">{{ is_array($beneficiario) ? $beneficiario['porcentaje'] ?? '' : ($beneficiario->porcentaje ?? '') }}%</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($client->tipoCliente != '390' && hasReferences($client->referenciasLaborales))
        <h3>Referencias Laborales</h3>
        <table class="content">
            <tr>
                <th>Nombre</th>
                <th>Telefono</th>
                <th>Relación</th>
            </tr>
            @foreach ($client->referenciasLaborales as $reference)
                <tr>
                    <td>{{ is_array($reference) ? $reference['nombre'] ?? '' : ($reference->nombre ?? '') }}</td>
                    <td>{{ is_array($reference) ? $reference['telefono'] ?? '' : ($reference->telefono ?? '') }}</td>
                    <td>{{ is_array($reference) ? $reference['afinidad'] ?? '' : ($reference->afinidad ?? '') }}</td>
                </tr>
            @endforeach
        </table>
    @elseif (hasReferences($client->referenciasComerciales))
        <h3>Referencias Comerciales</h3>
        <table class="content">
            <tr>
                <th>Nombre</th>
                <th>Telefono</th>
            </tr>
            @foreach ($client->referenciasComerciales as $reference)
                <tr>
                    <td>{{ is_array($reference) ? $reference['nombre'] ?? '' : ($reference->nombre ?? '') }}</td>
                    <td>{{ is_array($reference) ? $reference['telefono'] ?? '' : ($reference->telefono ?? '') }}</td>
                    <td>{{ is_array($reference) ? $reference['afinidad'] ?? '' : ($reference->afinidad ?? '') }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    @if (hasReferences($client->referenciasPersonales))
    <h3>Referencias Personales</h3>
    <table class="content">
        <tr>
            <th>Nombre</th>
            <th>Telefono</th>
            <th>Relación</th>
        </tr>
        @foreach ($client->referenciasPersonales as $reference)
            <tr>
                <td>{{ is_array($reference) ? $reference['nombre'] ?? '' : ($reference->nombre ?? '') }}</td>
                <td>{{ is_array($reference) ? $reference['telefono'] ?? '' : ($reference->telefono ?? '') }}</td>
                <td>{{ is_array($reference) ? $reference['afinidad'] ?? '' : ($reference->afinidad ?? '') }}</td>
            </tr>
        @endforeach
    </table>
    @endif
    @if (hasReferences($client->referenciasFamiliares))
    <h3>Referencias Familiares</h3>
    <table class="content">
        <tr>
            <th>Nombre</th>
            <th>Telefono</th>
            <th>Relación</th>
        </tr>
        @foreach ($client->referenciasFamiliares as $reference)
            <tr>
                <td>{{ is_array($reference) ? $reference['nombre'] ?? '' : ($reference->nombre ?? '') }}</td>
                <td>{{ is_array($reference) ? $reference['telefono'] ?? '' : ($reference->telefono ?? '') }}</td>
                <td>{{ is_array($reference) ? $reference['relacion'] ?? '' : ($reference->relacion ?? '') }}</td>
            </tr>
        @endforeach
    </table>
    @endif
    <table class="content">
        <tr>
            <td style="text-align: justify;">
                Yo
                @if($client->nombres || $client->apellidos)
                    <strong>{{ $client->nombres }} {{ $client->apellidos }}</strong>,
                @endif
                @if($client->dpi)
                    con CUI :
                    <strong>
                        {{ substr($client->dpi, 0, 4) }} {{ substr($client->dpi, 4, 5) }} {{ substr($client->dpi, 9, 4) }}
                    </strong>,
                @endif
                @if($client->nombreMunicipio)
                    originario de <strong>{{ $client->nombreMunicipio }}</strong>,
                @endif
                @if($client->nombreDepartamento)
                    @if($client->nombreMunicipio) del @endif departamento de <strong>{{ $client->nombreDepartamento }}</strong>,
                @endif
                @if($client->direccion)
                    con domicilio en <strong>{{ $client->direccion }}</strong>,
                @endif
                @if($client->telefono)
                    con número de teléfono <strong>{{ $client->telefono }}</strong>,
                @endif
                @if($client->estadoCivil)
                    de estado civil <strong>{{ $client->estadoCivil}}(a)</strong>,
                @endif
                @if($client->fecha_nacimiento)
                    con fecha de nacimiento
                    <strong>{{ Carbon::parse($client->fecha_nacimiento)->translatedFormat('d \d\e F \d\e Y') }}</strong>,
                @endif
                declaro de forma expresa que todos los datos e información que ahí apartados son veraces y que han sido consignados de forma voluntaria.
            </td>
        </tr>
        <tr>
            <td class="signature">
                F. ______________________ <br />
                @if($client->nombres || $client->apellidos)
                    {{ $client->nombres }} {{ $client->apellidos }} <br />
                @endif
                @if($client->dpi)
                    CUI: {{ substr($client->dpi, 0, 4) }} {{ substr($client->dpi, 4, 5) }} {{ substr($client->dpi, 9, 4) }}
                @endif
            </td>
        </tr>
    </table>
</body>

</html>
