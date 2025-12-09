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
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Solicitud de Financiamiento</title>
    <style>
         body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        h2{
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        th,
        td {
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .logo {
            width: 100px;
            height: auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        h1 {
            text-align: center;
        }

        .page-number {
            position: fixed;
            top: 10px;
            right: 20px;
            font-size: 11px;
            color: #666;
        }

        .page-number:before {
            content: "Página " counter(page);
        }

        .signatures-section {
            page-break-before: auto;
            page-break-inside: avoid;
            margin-top: 50px;
        }

        @media print {
            .signatures-section {
                page-break-before: auto;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="page-number"></div>
    <div class="header">
        <img src="{{ base64Image('images/logoNegro.png') }}" alt="Logo" class="logo">
    </div>
    <h1>SOLICITUD DE FINANCIAMIENTO</h1>
    <table>
        @if($prestamo->created_at)
            <tr>
                <td><strong>LUGAR Y FECHA:</strong></td>
                <td>San Marcos, San Marcos,
                    {{ Carbon::parse($prestamo->created_at)->translatedFormat('d \d\e F \d\e Y') }}
                </td>
            </tr>
        @endif
        @if($prestamo->asesor && $prestamo->asesor->name)
            <tr>
                <td><strong>ASESOR:</strong></td>
                <td>{{ $prestamo->asesor->name }}</td>
            </tr>
        @endif
    </table>
    <h2>I. DATOS GENERALES</h2>
    <table>
        @if($prestamo->cliente && ($prestamo->cliente->nombres || $prestamo->cliente->apellidos))
            <tr>
                <td colspan="2"><strong>NOMBRE COMPLETO:</strong></td>
                <td colspan="4">{{ $prestamo->cliente->nombres ?? '' }}
                    {{ $prestamo->cliente->apellidos ?? '' }}
                </td>
            </tr>
        @endif
        <tr>
            @if($prestamo->cliente && $prestamo->cliente->dpi)
                <td><strong>CUI:</strong></td>
                <td colspan="2">{{ $prestamo->cliente->dpi }}</td>
            @endif
            @if($prestamo->cliente && $prestamo->cliente->codigo)
                <td style="text-align: right;"><strong>No. CLIENTE:</strong></td>
                <td colspan="2">{{ $prestamo->cliente->codigo }}</td>
            @endif
        </tr>
        @if($prestamo->cliente && $prestamo->cliente->direccion)
            <tr>
                <td><strong>DIRECCION:</strong></td>
                <td colspan="4">{{ $prestamo->cliente->direccion }}</td>
            </tr>
        @endif
        <tr>
            @if($prestamo->cliente && $prestamo->cliente->telefono)
                <td><strong>TELEFONO:</strong></td>
                <td colspan="2">{{ $prestamo->cliente->telefono }}</td>
            @endif
            @if($prestamo->cliente && $prestamo->cliente->correo)
                <td style="text-align: right;"><strong>E-MAIL:</strong></td>
                <td colspan="3">{{ $prestamo->cliente->correo }}</td>
            @endif
        </tr>
        <tr>
            @if($prestamo->cliente && $prestamo->cliente->genero)
                <td><strong>SEXO:</strong></td>
                <td>{{ $prestamo->cliente->genero }}</td>
            @endif
            @if($prestamo->cliente && $prestamo->cliente->estadoCivil)
                <td colspan="2" style="text-align: right;"><strong>ESTADO CIVIL:</strong></td>
                <td>{{ $prestamo->cliente->estadoCivil }}</td>
            @endif
        </tr>
        <tr>
            @if($prestamo->cliente && $prestamo->cliente->fecha_nacimiento)
                <td><strong>EDAD:</strong></td>
                <td>{{ Carbon::parse($prestamo->cliente->fecha_nacimiento)->age }} años</td>
                <td colspan="2" style="text-align: right;"><strong>FECHA DE NACIMIENTO:</strong></td>
                <td colspan="2">{{ Carbon::parse($prestamo->cliente->fecha_nacimiento)->translatedFormat('d \d\e F \d\e Y') }}</td>
            @endif
        </tr>
        @if($prestamo->cliente && $prestamo->cliente->nit)
            <tr>
                <td><strong>NIT:</strong></td>
                <td>{{ $prestamo->cliente->nit }}</td>
            </tr>
        @endif
        @if($prestamo->cliente && $prestamo->cliente->nivel_academico)
            <tr>
                <td colspan="2"><strong>GRADO DE ESCOLARIDAD:</strong></td>
                <td>{{ $prestamo->cliente->nivel_academico }}</td>
            </tr>
        @endif
        @if($prestamo->cliente && $prestamo->cliente->profesion)
            <tr>
                <td colspan="2"><strong>PROFESION U OFICIO:</strong></td>
                <td>{{ $prestamo->cliente->profesion }}</td>
            </tr>
        @endif
        @if($prestamo->cliente && $prestamo->cliente->nombreTipoCliente)
            <tr>
                <td colspan="2"><strong>OCUPACION:</strong></td>
                <td>{{ $prestamo->cliente->nombreTipoCliente }}</td>
            </tr>
        @endif
        @if($prestamo->cliente && ($prestamo->cliente->conyuge || $prestamo->cliente->estadoCivil != 'Soltero'))
            <tr>
                <td colspan="2"><strong>NOMBRE DEL CONYUGE:</strong></td>
                <td colspan="4">
                    {{ $prestamo->cliente->estadoCivil == 'Soltero' ? 'No Aplica' : ($prestamo->cliente->conyuge ?? 'No ingresada') }}
                </td>
            </tr>
        @endif
        @if($prestamo->cliente && ($prestamo->cliente->cargas_familiares || $prestamo->cliente->estadoCivil != 'Soltero'))
            <tr>
                <td colspan="2"><strong>CARGAS FAMILIARES:</strong></td>
                <td>{{ $prestamo->cliente->estadoCivil == 'Soltero' ? 'No Aplica' : ($prestamo->cliente->cargas_familiares ?? 'No ingresada') }}
                </td>
            </tr>
        @endif
        @if($prestamo->cliente && ($prestamo->cliente->integrantes_nucleo_familiar || $prestamo->cliente->estadoCivil != 'Soltero'))
            <tr>
                <td colspan="4"><strong>No. INTEGRANTES DE SU NUCLEO FAMILIAR:</strong></td>
                <td>{{ $prestamo->cliente->estadoCivil == 'Soltero' ? 'No Aplica' : ($prestamo->cliente->integrantes_nucleo_familiar ?? 'No ingresada') }}
                </td>
            </tr>
        @endif
        @if($prestamo->cliente && $prestamo->cliente->casa_donde_vive)
            <tr>
                <td colspan="4"><strong>LA CASA DONDE VIVE ES:</strong></td>
                <td>{{ $prestamo->cliente->casa_donde_vive }}</td>
            </tr>
        @endif
        @if($prestamo->cliente && $prestamo->cliente->estabilidad_domiciliaria)
            <tr>
                <td colspan="4"><strong>TIEMPO DE ESTABILIDAD DOMICILIAR:</strong></td>
                <td>{{ $prestamo->cliente->estabilidad_domiciliaria }} año(s)</td>
            </tr>
        @endif
    </table>
    @if($prestamo->cliente && ($prestamo->cliente->nombreTipoCliente || $prestamo->cliente->nombreEmpresa || $prestamo->cliente->direccionEmpresa || $prestamo->cliente->telefonoEmpresa || $prestamo->cliente->puesto || $prestamo->cliente->fechaInicio))
        <h2>II. DATOS LABORALES</h2>
        <table>
            @if($prestamo->cliente->nombreTipoCliente)
                <tr>
                    <td><strong>TIPO DE LABOR:</strong></td>
                    <td>{{ $prestamo->cliente->nombreTipoCliente }}</td>
                </tr>
            @endif
            @if($prestamo->cliente->nombreEmpresa)
                <tr>
                    <td colspan="4"><strong>NOMBRE DE LA EMPRESA DONDE TRABAJA, NEGOCIO O ACTIVIDAD:</strong></td>
                </tr>
                <tr>
                    <td>{{ $prestamo->cliente->nombreEmpresa }}</td>
                </tr>
            @endif
            @if($prestamo->cliente->direccionEmpresa)
                <tr>
                    <td><strong>DIRECCION:</strong></td>
                    <td>{{ $prestamo->cliente->direccionEmpresa }}</td>
                </tr>
            @endif
            @if($prestamo->cliente->telefonoEmpresa)
                <tr>
                    <td><strong>TELEFONO:</strong></td>
                    <td>{{ $prestamo->cliente->telefonoEmpresa }}</td>
                </tr>
            @endif
            @if($prestamo->cliente->puesto)
                <tr>
                    <td><strong>CARGO:</strong></td>
                    <td>{{ $prestamo->cliente->puesto }}</td>
                </tr>
            @endif
            @if($prestamo->cliente->fechaInicio)
                <tr>
                    <td><strong>FECHA DE INICIO:</strong></td>
                    <td>{{ Carbon::parse($prestamo->cliente->fechaInicio)->translatedFormat('d \d\e F \d\e Y') }}</td>
                </tr>
            @endif
        </table>
        <br />
    @endif
    @if($prestamo->cliente && (hasReferences($prestamo->cliente->referenciasLaborales) || hasReferences($prestamo->cliente->referenciasComerciales) || hasReferences($prestamo->cliente->referenciasPersonales) || hasReferences($prestamo->cliente->referenciasFamiliares)))
        <h2>III. REFERENCIAS</h2>
        @if ($prestamo->cliente->tipoCliente != '390' && hasReferences($prestamo->cliente->referenciasLaborales))
            <h3>Referencias Laborales</h3>
            <table class="content">
                <tr>
                    <th>Nombre</th>
                    <th>Telefono</th>
                </tr>
                @foreach ($prestamo->cliente->referenciasLaborales as $reference)
                    @if((is_array($reference) && ($reference['nombre'] ?? $reference['telefono'])) || (is_object($reference) && ($reference->nombre || $reference->telefono)))
                        <tr>
                            <td>{{ is_array($reference) ? ($reference['nombre'] ?? '') : ($reference->nombre ?? '') }}</td>
                            <td>{{ is_array($reference) ? ($reference['telefono'] ?? '') : ($reference->telefono ?? '') }}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        @elseif($prestamo->cliente->tipoCliente == '390' && hasReferences($prestamo->cliente->referenciasComerciales))
            <h3>Referencias Comerciales</h3>
            <table class="content">
                <tr>
                    <th>Nombre</th>
                    <th>Telefono</th>
                </tr>
                @foreach ($prestamo->cliente->referenciasComerciales as $reference)
                    @if((is_array($reference) && ($reference['nombre'] ?? $reference['telefono'])) || (is_object($reference) && ($reference->nombre || $reference->telefono)))
                        <tr>
                            <td>{{ is_array($reference) ? ($reference['nombre'] ?? '') : ($reference->nombre ?? '') }}</td>
                            <td>{{ is_array($reference) ? ($reference['telefono'] ?? '') : ($reference->telefono ?? '') }}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        @endif

        @if(hasReferences($prestamo->cliente->referenciasPersonales))
            <h3>Referencias Personales</h3>
            <table class="content">
                <tr>
                    <th>Nombre</th>
                    <th>Telefono</th>
                </tr>
                @foreach ($prestamo->cliente->referenciasPersonales as $reference)
                    @if((is_array($reference) && ($reference['nombre'] ?? $reference['telefono'])) || (is_object($reference) && ($reference->nombre || $reference->telefono)))
                        <tr>
                            <td>{{ is_array($reference) ? ($reference['nombre'] ?? '') : ($reference->nombre ?? '') }}</td>
                            <td>{{ is_array($reference) ? ($reference['telefono'] ?? '') : ($reference->telefono ?? '') }}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        @endif

        @if(hasReferences($prestamo->cliente->referenciasFamiliares))
            <h3>Referencias Familiares</h3>
            <table class="content">
                <tr>
                    <th>Nombre</th>
                    <th>Telefono</th>
                </tr>
                @foreach ($prestamo->cliente->referenciasFamiliares as $reference)
                    @if((is_array($reference) && ($reference['nombre'] ?? $reference['telefono'])) || (is_object($reference) && ($reference->nombre || $reference->telefono)))
                        <tr>
                            <td>{{ is_array($reference) ? ($reference['nombre'] ?? '') : ($reference->nombre ?? '') }}</td>
                            <td>{{ is_array($reference) ? ($reference['telefono'] ?? '') : ($reference->telefono ?? '') }}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        @endif
    @endif

    <h2>IV. DEL CREDITO</h2>
    <table>
        <tr>
            @if($prestamo->monto)
                <td><strong>MONTO SOLICITADO:</strong></td>
                <td colspan="2">Q. {{ number_format($prestamo->monto, 2) }}</td>
            @endif
            @if($prestamo->nombreDestino)
                <td><strong>DESTINO:</strong></td>
                <td>{{ $prestamo->nombreDestino }}</td>
            @endif
        </tr>
        @if($prestamo->uso_prestamo)
            <tr>
                <td><strong>USO DEL FINANCIAMIENTO:</strong></td>
                <td>{{ $prestamo->uso_prestamo }}</td>
            </tr>
        @endif
        <tr>
            @if($prestamo->interes)
                <td><strong>TASA DE INTERES:</strong></td>
                <td>{{ $prestamo->interes }}% mensual</td>
            @endif
            @if($prestamo->plazo && $prestamo->tipoPlazo)
                <td><strong>PLAZO:</strong></td>
                <td>{{ $prestamo->plazo }} {{ $prestamo->tipoPlazo->nombre }}</td>
            @endif
            @if($prestamo->nombreFrecuenciaPago)
                <td><strong>FRECUENCIA PAGO:</strong></td>
                <td>{{ $prestamo->nombreFrecuenciaPago }}</td>
            @endif
        </tr>
    </table>
    @if($prestamo->propiedad && ($prestamo->propiedad->nombreTipo || $prestamo->propiedad->Descripcion || $prestamo->propiedad->Direccion))
        <h2>V. GARANTIA</h2>
        <table>
            @if($prestamo->propiedad->nombreTipo)
                <tr>
                    <td><strong>TIPO DE GARANTIA:</strong></td>
                    <td>{{ $prestamo->propiedad->nombreTipo }}</td>
                </tr>
            @endif
            @if($prestamo->propiedad->Descripcion)
                <tr>
                    <td><strong>DESCRIPCION DE LA GARANTIA:</strong></td>
                </tr>
                <tr>
                    @php
                        $descripcion = $prestamo->propiedad->Descripcion;
                        $maxLength = 75; // caracteres por fila
                        $lineasDescripcion = str_split($descripcion, $maxLength);
                    @endphp
                    @foreach($lineasDescripcion as $linea)
                        <tr>
                            <td colspan="4">{{ $linea }}</td>
                        </tr>
                    @endforeach
                </tr>
            @endif
            @if($prestamo->propiedad->Direccion)
                <tr>
                    <td><strong>DIRECCION DE LA GARANTIA:</strong></td>
                </tr>
                <tr>
                    @php
                        $direccion = $prestamo->propiedad->Direccion;
                        $maxLength = 60; // caracteres por fila
                        $lineas = str_split($direccion, $maxLength);
                    @endphp
                    @foreach($lineas as $linea)
                        <tr>
                            <td>{{ $linea }}</td>
                        </tr>
                    @endforeach
                </tr>
            @endif
        </table>
    @endif
    @if($prestamo->fiador_dpi && $prestamo->fiador)
        <h2>VI. DATOS DEL CODEUDOR/FIADOR</h2>
        <table>
            @if($prestamo->fiador->nombres || $prestamo->fiador->apellidos)
                <tr>
                    <td><strong>NOMBRE:</strong></td>
                    <td colspan="3">{{ $prestamo->fiador->nombres ?? '' }}
                        {{ $prestamo->fiador->apellidos ?? '' }}
                    </td>
                </tr>
            @endif
            <tr>
                @if($prestamo->fiador->dpi)
                    <td><strong>CUI:</strong></td>
                    <td colspan="2">{{ $prestamo->fiador->dpi }}</td>
                @endif
                @if($prestamo->fiador->codigo)
                    <td><strong>No. DE CUENTA:</strong></td>
                    <td colspan="3">{{ $prestamo->fiador->codigo }}</td>
                @endif
            </tr>
            @if($prestamo->fiador->direccion)
                <tr>
                    <td><strong>DIRECCION:</strong></td>
                    <td colspan="3">{{ $prestamo->fiador->direccion }}</td>
                </tr>
            @endif
            <tr>
                @if($prestamo->parentesco)
                    <td><strong>PARENTESCO:</strong></td>
                    <td colspan="2">{{ $prestamo->parentesco }}</td>
                @endif
                @if($prestamo->fiador->fecha_nacimiento)
                    <td><strong>EDAD:</strong></td>
                    <td>{{ Carbon::parse($prestamo->fiador->fecha_nacimiento)->age }} años</td>
                @endif
            </tr>
            <tr>
                @if($prestamo->fiador->estadoCivil)
                    <td colspan="2"><strong>ESTADO CIVIL:</strong></td>
                    <td>{{ $prestamo->fiador->estadoCivil }}</td>
                @endif
                @if($prestamo->fiador->telefono)
                    <td><strong>TELEFONO:</strong></td>
                    <td>{{ $prestamo->fiador->telefono }}</td>
                @endif
            </tr>
            @if($prestamo->fiador->nivel_academico)
                <tr>
                    <td colspan="2"><strong>GRADO DE ESCOLARIDAD:</strong></td>
                    <td>{{ $prestamo->fiador->nivel_academico }}</td>
                </tr>
            @endif
            @if($prestamo->fiador->profesion)
                <tr>
                    <td colspan="2"><strong>PROFESION U OFICIO:</strong></td>
                    <td>{{ $prestamo->fiador->profesion }}</td>
                </tr>
            @endif
            @if($prestamo->fiador->nombreTipoCliente)
                <tr>
                    <td colspan="2"><strong>OCUPACION:</strong></td>
                    <td>{{ $prestamo->fiador->nombreTipoCliente }}</td>
                </tr>
            @endif
            @if($prestamo->fiador->cargas_familiares || $prestamo->fiador->estadoCivil != 'Soltero')
                <tr>
                    <td colspan="2"><strong>CARGAS FAMILIARES:</strong></td>
                    <td>{{ $prestamo->fiador->estadoCivil == 'Soltero' ? 'No Aplica' : ($prestamo->fiador->cargas_familiares ?? 'No ingresada') }}
                    </td>
                </tr>
            @endif
            @if($prestamo->fiador->integrantes_nucleo_familiar || $prestamo->fiador->estadoCivil != 'Soltero')
                <tr>
                    <td colspan="4"><strong>No. INTEGRANTES DE SU NUCLEO FAMILIAR:</strong></td>
                    <td>{{ $prestamo->fiador->estadoCivil == 'Soltero' ? 'No Aplica' : ($prestamo->fiador->integrantes_nucleo_familiar ?? 'No ingresada') }}
                    </td>
                </tr>
            @endif
            @if($prestamo->fiador->ingresos_mensuales)
                <tr>
                    <td colspan="4"><strong>INGRESOS MENSUALES APROXIMADOS:</strong></td>
                    <td>Q. {{ number_format($prestamo->fiador->ingresos_mensuales, 2) }}</td>
                </tr>
            @endif
            @if($prestamo->fiador->egresos_mensuales)
                <tr>
                    <td colspan="4"><strong>EGRESOS MENSUALES APROX:</strong></td>
                    <td>Q. {{ number_format($prestamo->fiador->egresos_mensuales, 2) }}</td>
                </tr>
            @endif
        </table>
    @endif
    <br />
    <br />

        <table>
            <tr>
                <td style="text-align: center;">________________________</td>
                <td></td>
                <td style="text-align: center;">________________________</td>
                <td></td>
            </tr>
            <tr>
                <td style="text-align: center;"><strong>FIRMA DEL DEUDOR</strong></td>
                <td><strong></strong></td>
                <td style="text-align: center;"><strong>
                        @if($prestamo->fiador_dpi)
                            FIRMA DEL CODEUDOR/FIADOR
                        @else
                            Firma del Asesor
                        @endif </strong></td>
                <td><strong></strong></td>
            </tr>
            @if($prestamo->fiador_dpi)
                <tr></tr>
                <tr>
                    <td colspan="4" style="text-align: center; height: 45px;">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: center;">_____________________</td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: center;"><strong>Firma del Asesor</strong></td>
                </tr>
            @endif
        </table>

    <script type="text/php">
        if (isset($pdf)) {
            $x = 520;
            $y = 15;
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 9;
            $color = array(0.5,0.5,0.5);
            $pdf->page_text($x, $y, $text, $font, $size, $color);
        }
    </script>
</body>

</html>
