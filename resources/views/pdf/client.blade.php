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
@endphp
<!DOCTYPE html>
<html>

<head>
    <title>Client PDF</title>
    <style>
        /* Agrega tus estilos aquí */
        body {
            font-family: Arial, sans-serif;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
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
            margin: 10px;
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
            Cliente: {{ $client->nombres }} {{ $client->apellidos }} ({{ $client->codigo }})
        </h2>
    </div>
    <table class="content">
        <tr>
            <td><strong>Nombre:</strong></td>
            <td>{{ $client->nombres }} {{ $client->apellidos }}</td>
            <td><strong>CUI:</strong></td>
            <td>{{ $client->dpi}}</td>
            <td><strong>Tipo De Cliente:</strong></td>
            <td>{{ $client->nombreTipoCliente}}</td>
        </tr>
    </table>
    <table class="content">
        <tr>
            <td><strong>Email:</strong></td>
            <td colspan="2">{{ $client->correo }}</td>
            <td><strong>Telefono:</strong></td>
            <td colspan="2">{{ $client->telefono }}</td>
        </tr>
        <tr>
            <td><strong>Direccion:</strong></td>
            <td colspan="5">{{ $client->direccion }}</td>
        </tr>
        <tr>
            <td><strong>Municipio:</strong></td>
            <td colspan="2">{{ $client->nombreMunicipio }}</td>
            <td><strong>Departamento:</strong></td>
            <td colspan="2">{{ $client->nombreDepartamento }}</td>
        </tr>
        <tr>
            <td><strong>Fecha de Nacimiento:</strong></td>
            <td>{{ Carbon::parse($client->fecha_nacimiento)->translatedFormat('d \d\e F \d\e Y') }}</td>
            <td><strong>Genero:</strong></td>
            <td>{{ $client->genero }}</td>
            <td><strong>Estado Civil:</strong></td>
            <td>{{ $client->estadoCivil }}</td>
        </tr>
    </table>
    <table class="content">
        <tr>
            <td><strong>Nivel Academico:</strong></td>
            <td>{{ $client->nivel_academico }}</td>
            <td><strong>Profesion:</strong></td>
            <td>{{ $client->profesion }}</td>
        </tr>
    </table>
    <table class="content">
        <tr>
            <td><strong>Nombre de la Empresa:</strong></td>
            <td>{{ $client->nombreEmpresa }}</td>
            <td><strong>Fecha de Inicio:</strong></td>
            <td>{{ Carbon::parse($client->fechaInicio)->translatedFormat('d \d\e F \d\e Y') }}</td>
        </tr>
        <tr>
            <td><strong>Direccion de la Empresa:</strong></td>
            <td colspan="3">{{ $client->direccionEmpresa }}</td>
        </tr>
        <tr>
            <td><strong>Telefono de la Empresa:</strong></td>
            <td colspan="3">{{ $client->telefonoEmpresa }}</td>
        </tr>
        @if ($client->tipoCliente == '390')
            <tr>
                <td><strong>Número de Patente:</strong></td>
                <td>{{ $client->numeroPatente }}</td>
                <td><strong>NIT:</strong></td>
                <td>{{ $client->nit }}</td>
            </tr>
        @else
            <tr>
                <td><strong>Puesto:</strong></td>
                <td colspan="3">{{ $client->puesto }}</td>
            </tr>
        @endif
        <tr>
            <td><strong>Ingresos Mensuales:</strong></td>
            <td>{{ $client->ingresos_mensuales }}</td>
            <td><strong>Egresos Mensuales:</strong></td>
            <td>{{ $client->egresos_mensuales }}</td>
        </tr>
        @if ($client->tipoCliente != '390')
            <tr>
                <td><strong>Ingresos Mensuales:</strong></td>
                <td colspan="3">{{ $client->otrosIngresos }}</td>
            </tr>
        @endif
    </table>

    @if ($client->estado_civil == '18')
        <h3>Información Familiar</h3>
        <table class="content">
            <tr>
                <td><strong>Conyuge:</strong></td>
                <td>{{ $client->conyuge }}</td>
            </tr>
            <tr>
                <td><strong>Cargas Familiares:</strong></td>
                <td>{{ $client->cargas_familiares }}</td>
            </tr>
            <tr>
                <td><strong>No. Integrantes nucleo familar</strong></td>
                <td>{{ $client->integrantes_nucleo_familiar }}</td>
            </tr>
            <tr>
                <td><strong>La casa donde vive:</strong></td>
                <td>{{ $client->tipo_vivienda }}</td>
            </tr>
            <tr>
                <td><strong>Tiempo de estabilidad domiciliar:</strong></td>
                <td>{{ $client->estabilidad_domiciliaria }} año(s)</td>
            </tr>
        </table>

    @endif
    @if ($client->tipoCliente != '390')
        <h3>Referencias Laborales</h3>
        <table class="content">
            <tr>
                <th>Nombre</th>
                <th>Telefono</th>
            </tr>
            @if(!empty($client->referenciasLaborales) && (is_array($client->referenciasLaborales) || is_object($client->referenciasLaborales)))
                @foreach ($client->referenciasLaborales as $reference)
                    <tr>
                        <td>{{ is_array($reference) ? $reference['nombre'] ?? '' : ($reference->nombre ?? '') }}</td>
                        <td>{{ is_array($reference) ? $reference['telefono'] ?? '' : ($reference->telefono ?? '') }}</td>
                    </tr>
                @endforeach
            @endif
        </table>
    @else
        <h3>Referencias Comerciales</h3>
        <table class="content">
            <tr>
                <th>Nombre</th>
                <th>Telefono</th>
            </tr>
            @if(!empty($client->referenciasComerciales) && (is_array($client->referenciasComerciales) || is_object($client->referenciasComerciales)))
                @foreach ($client->referenciasComerciales as $reference)
                    <tr>
                        <td>{{ is_array($reference) ? $reference['nombre'] ?? '' : ($reference->nombre ?? '') }}</td>
                        <td>{{ is_array($reference) ? $reference['telefono'] ?? '' : ($reference->telefono ?? '') }}</td>
                    </tr>
                @endforeach
            @endif
        </table>
    @endif
    <h3>Referencias Personales</h3>
    <table class="content">
        <tr>
            <th>Nombre</th>
            <th>Telefono</th>
        </tr>
        @if(!empty($client->referenciasPersonales) && (is_array($client->referenciasPersonales) || is_object($client->referenciasPersonales)))
            @foreach ($client->referenciasPersonales as $reference)
                <tr>
                    <td>{{ is_array($reference) ? $reference['nombre'] ?? '' : ($reference->nombre ?? '') }}</td>
                    <td>{{ is_array($reference) ? $reference['telefono'] ?? '' : ($reference->telefono ?? '') }}</td>
                </tr>
            @endforeach
        @endif
    </table>

    <h3>Referencias Familiares</h3>
    <table class="content">
        <tr>
            <th>Nombre</th>
            <th>Telefono</th>
        </tr>
        @if(!empty($client->referenciasFamiliares) && (is_array($client->referenciasFamiliares) || is_object($client->referenciasFamiliares)))
            @foreach ($client->referenciasFamiliares as $reference)
                <tr>
                    <td>{{ is_array($reference) ? $reference['nombre'] ?? '' : ($reference->nombre ?? '') }}</td>
                    <td>{{ is_array($reference) ? $reference['telefono'] ?? '' : ($reference->telefono ?? '') }}</td>
                </tr>
            @endforeach
        @endif
    </table>
    <table class="content">
        <tr>
            <td>
                Yo <strong>{{ $client->nombres }} {{ $client->apellidos }}</strong> con CUI
                <strong>{{ $client->dpi }}</strong> originario de <strong>{{ $client->nombreMunicipio }}</strong> del
                departamento de <strong>{{ $client->nombreDepartamento }}</strong> con domicilio en
                <strong>{{ $client->direccion }}</strong> con número de teléfono
                <strong>{{ $client->telefono }}</strong> de estado civil
                <strong>{{ $client->estadoCivil }}</strong> con fecha de nacimiento
                <strong>{{ Carbon::parse($client->fecha_nacimiento)->translatedFormat('d \d\e F \d\e Y') }}</strong>
                declaro de forma
                expresa
                que todos los datos e información que ahí apartados son veraces y que han sido consignados de forma
                voluntaria.
            </td>
        </tr>
        <tr>
            <td class="signature"> F. ______________________ <br />
                {{ $client->nombres }} {{ $client->apellidos }} <br />
                CUI. {{$client->dpi}} </td>
        </tr>
    </table>
</body>

</html>
