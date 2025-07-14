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
        <tr>
            <td><strong>LUGAR Y FECHA:</strong></td>
            <td>San Marcos, San Marcos,
                {{ Carbon::parse($prestamo->created_at)->translatedFormat('d \d\e F \d\e Y') }}
            </td>
        </tr>
        <tr>
            <td><strong>ASESOR:</strong></td>
            <td>{{ $prestamo->asesor->name ?? 'No ingresada' }}</td>
        </tr>
    </table>
    <h2>I. DATOS GENERALES</h2>
    <table>
        <tr>
            <td colspan="2"><strong>NOMBRE COMPLETO:</strong></td>
            <td colspan="4">{{ $prestamo->cliente->nombres ?? 'No ingresada' }}
                {{ $prestamo->cliente->apellidos ?? 'No ingresada' }}
            </td>
        </tr>
        <tr>
            <td><strong>CUI:</strong></td>
            <td colspan="2">{{ $prestamo->cliente->dpi ?? 'No ingresada' }}</td>
            <td style="text-align: right;"><strong>No. CLIENTE:</strong></td>
            <td colspan="2">{{ $prestamo->cliente->codigo ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>DIRECCION:</strong></td>
            <td colspan="4">{{ $prestamo->cliente->direccion ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>TELEFONO:</strong></td>
            <td colspan="2">{{ $prestamo->cliente->telefono ?? 'No ingresada' }}</td>
            <td style="text-align: right;"><strong>E-MAIL:</strong></td>
            <td colspan="3">{{ $prestamo->cliente->correo ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>SEXO:</strong></td>
            <td>{{ $prestamo->cliente->genero ?? 'No ingresada' }}</td>
            <td colspan="2" style="text-align: right;"><strong>ESTADO CIVIL:</strong></td>
            <td>{{ $prestamo->cliente->estadoCivil ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>EDAD:</strong></td>
            <td>{{ $prestamo->cliente->fecha_nacimiento ? Carbon::parse($prestamo->cliente->fecha_nacimiento)->age . ' años' : 'No ingresada' }}
            </td>
            <td colspan="2" style="text-align: right;"><strong>FECHA DE NACIMIENTO:</strong></td>
            <td colspan="2">
                {{ $prestamo->cliente->fecha_nacimiento ? Carbon::parse($prestamo->cliente->fecha_nacimiento)->translatedFormat('d \d\e F \d\e Y') : 'No ingresada' }}
            </td>
        </tr>
        <tr>
            <td><strong>NIT:</strong></td>
            <td>{{ $prestamo->cliente->nit ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>GRADO DE ESCOLARIDAD:</strong></td>
            <td>{{ $prestamo->cliente->nivel_academico ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>PROFESION U OFICIO:</strong></td>
            <td>{{$prestamo->cliente->profesion}}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>OCUPACION:</strong></td>
            <td>{{$prestamo->cliente->nombreTipoCliente}}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>NOMBRE DEL CONYUGE:</strong></td>
            <td colspan="4>
                {{ $prestamo->cliente->estadoCivil == 'Soltero' ? 'No Aplica' : ($prestamo->cliente->conyuge ?? 'No ingresada') }}
            </td>
        </tr>
        <tr>
            <td colspan="2"><strong>CARGAS FAMILIARES:</strong></td>
            <td>{{ $prestamo->cliente->estadoCivil == 'Soltero' ? 'No Aplica' : ($prestamo->cliente->cargas_familiares ?? 'No ingresada') }}
            </td>
        </tr>
        <tr>
            <td colspan="4"><strong>No. INTEGRANTES DE SU NUCLEO FAMILIAR:</strong></td>
            <td>{{ $prestamo->cliente->estadoCivil == 'Soltero' ? 'No Aplica' : ($prestamo->cliente->integrantes_nucleo_familiar ?? 'No ingresada') }}
            </td>
        </tr>
        <tr>
            <td colspan="4"><strong>LA CASA DONDE VIVE ES:</strong></td>
            <td>{{ $prestamo->cliente->casa_donde_vive ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td colspan="4"><strong>TIEMPO DE ESTABILIDAD DOMICILIAR:</strong></td>
            <td>{{ $prestamo->cliente->estabilidad_domiciliaria ? $prestamo->cliente->estabilidad_domiciliaria . ' año(s)' : 'No ingresada' }}
            </td>
        </tr>
    </table>
    <h2>II. DATOS LABORALES</h2>
    <table>
        <tr>
            <td><strong>TIPO DE LABOR:</strong></td>
            <td>{{ $prestamo->cliente->nombreTipoCliente ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td colspan="4"><strong>NOMBRE DE LA EMPRESA DONDE TRABAJA, NEGOCIO O ACTIVIDAD:</strong></td>
        </tr>
        <tr>
            <td>{{ $prestamo->cliente->nombreEmpresa ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>DIRECCION:</strong></td>
            <td>{{ $prestamo->cliente->direccionEmpresa ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>TELEFONO:</strong></td>
            <td>{{ $prestamo->cliente->telefonoEmpresa ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>CARGO:</strong></td>
            <td>{{ $prestamo->cliente->puesto ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>FECHA DE INICIO:</strong></td>
            <td>{{ $prestamo->cliente->fechaInicio ? Carbon::parse($prestamo->cliente->fechaInicio)->translatedFormat('d \d\e F \d\e Y') : 'No ingresada' }}
            </td>
        </tr>
    </table>
    <br />
    <h2>III. REFERENCIAS</h2>
    @if ($prestamo->cliente->tipoCliente != '390')
        <h3>Referencias Laborales</h3>
        <table class="content">
            <tr>
                <th>Nombre</th>
                <th>Telefono</th>
            </tr>
            @foreach ($prestamo->cliente->referenciasLaborales as $reference)
                <tr>
                    <td>{{ $reference->nombre ?? 'No ingresada' }}</td>
                    <td>{{ $reference->telefono ?? 'No ingresada' }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <h3>Referencias Comerciales</h3>
        <table class="content">
            <tr>
                <th>Nombre</th>
                <th>Telefono</th>
            </tr>
            @foreach ($prestamo->cliente->referenciasComerciales as $reference)
                <tr>
                    <td>{{ $reference['nombre'] ?? 'No ingresada' }}</td>
                    <td>{{ $reference['telefono'] ?? 'No ingresada' }}</td>

                </tr>
            @endforeach
        </table>
    @endif
    <h3>Referencias Personales</h3>
    <table class="content">
        <tr>
            <th>Nombre</th>
            <th>Telefono</th>
        </tr>
        @foreach ($prestamo->cliente->referenciasPersonales as $reference)
            <tr>
                <td>{{ $reference['nombre'] ?? 'No ingresada' }}</td>
                <td>{{ $reference['telefono'] ?? 'No ingresada' }}</td>
            </tr>
        @endforeach
    </table>

    <h3>Referencias Familiares</h3>
    <table class="content">
        <tr>
            <th>Nombre</th>
            <th>Telefono</th>
        </tr>
        @foreach ($prestamo->cliente->referenciasFamiliares as $reference)
            <tr>
                <td>{{ $reference['nombre'] ?? 'No ingresada' }}</td>
                <td>{{ $reference['telefono'] ?? 'No ingresada' }}</td>
            </tr>
        @endforeach
    </table>

    <h2>IV. DEL CREDITO</h2>
    <table>
        <tr>
            <td><strong>MONTO SOLICITADO:</strong></td>
            <td colspan="2">Q. {{ number_format($prestamo->monto, 2) }}</td>
            <td><strong>DESTINO:</strong></td>
            <td>{{ $prestamo->nombreDestino ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>USO DEL FINANCIAMIENTO:</strong></td>
            <td>{{ $prestamo->uso_prestamo ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>TASA DE INTERES:</strong></td>
            <td>{{ $prestamo->interes ?? 'No ingresada' }}% mensual</td>
            <td><strong>PLAZO:</strong></td>
            <td>{{ $prestamo->plazo ? $prestamo->plazo . ' ' . $prestamo->tipoPlazo->nombre : 'No ingresada' }}</td>
            <td><strong>FRECUENCIA PAGO:</strong></td>
            <td>{{ $prestamo->nombreFrecuenciaPago ?? 'No ingresada' }}</td>
        </tr>
    </table>
    <h2>V. GARANTIA</h2>
    <table>
        <tr>
            <td><strong>TIPO DE GARANTIA:</strong></td>
            <td>{{ $prestamo->propiedad->nombreTipo ?? 'No ingresada' }}</td>
        </tr>
        <tr>
            <td><strong>DESCRIPCION DE LA GARANTIA:</strong></td>
        </tr>
        <tr>
            @php
                $descripcion = $prestamo->propiedad->Descripcion ?? 'No ingresada';
                $maxLength = 60; // caracteres por fila
                $lineasDescripcion = [];
                if ($descripcion !== 'No ingresada') {
                    $lineasDescripcion = str_split($descripcion, $maxLength);
                } else {
                    $lineasDescripcion[] = $descripcion;
                }
            @endphp
            @foreach($lineasDescripcion as $linea)
                <tr>
                    <td colspan="4">{{ $linea }}</td>
                </tr>
            @endforeach
        </tr>
        <tr>
            <td><strong>DIRECCION DE LA GARANTIA:</strong></td>
        </tr>
        <tr>
            @php
                $direccion = $prestamo->propiedad->Direccion ?? 'No ingresada';
                $maxLength = 60; // caracteres por fila
                $lineas = [];
                if ($direccion !== 'No ingresada') {
                    $lineas = str_split($direccion, $maxLength);
                } else {
                    $lineas[] = $direccion;
                }
            @endphp
            @foreach($lineas as $linea)
                <tr>
                    <td>{{ $linea }}</td>
                </tr>
            @endforeach
        </tr>
    </table>
    @if($prestamo->fiador_dpi)
        <h2>VI. DATOS DEL CODEUDOR/FIADOR</h2>
        <table>
            <tr>
                <td><strong>NOMBRE:</strong></td>
                <td colspan="3">{{ $prestamo->fiador->nombres ?? 'No ingresada' }}
                    {{ $prestamo->fiador->apellidos ?? 'No ingresada' }}
                </td>
            </tr>
            <tr>
                <td><strong>CUI:</strong></td>
                <td colspan="2">{{ $prestamo->fiador->dpi ?? 'No ingresada' }}</td>
                <td><strong>No. DE CUENTA:</strong></td>
                <td colspan="3">{{ $prestamo->fiador->codigo ?? 'No ingresada' }}</td>
            </tr>
            <tr>
                <td><strong>DIRECCION:</strong></td>
                <td colspan="3">{{ $prestamo->fiador->direccion ?? 'No ingresada' }}</td>
            </tr>
            <tr>
                <td><strong>PARENTESCO:</strong></td>
                <td colspan="2">{{ $prestamo->parentesco ?? 'No ingresada' }}</td>
                <td><strong>EDAD:</strong></td>
                <td>{{ $prestamo->fiador->fecha_nacimiento ? Carbon::parse($prestamo->fiador->fecha_nacimiento)->age . ' años' : 'No ingresada' }}
                </td>
            </tr>
            <tr>
                <td colspan="2"><strong>ESTADO CIVIL:</strong></td>
                <td>{{ $prestamo->fiador->estadoCivil ?? 'No ingresada' }}</td>
                <td><strong>TELEFONO:</strong></td>
                <td>{{ $prestamo->fiador->telefono ?? 'No ingresada' }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>GRADO DE ESCOLARIDAD:</strong></td>
                <td>{{ $prestamo->fiador->nivel_academico ?? 'No ingresada' }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>PROFESION U OFICIO:</strong></td>
                <td>{{ $prestamo->fiador->profesion ?? 'No ingresada' }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>OCUPACION:</strong></td>
                <td>{{ $prestamo->fiador->nombreTipoCliente ?? 'No ingresada' }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>CARGAS FAMILIARES:</strong></td>
                <td>{{ $prestamo->fiador->estadoCivil == 'Soltero' ? 'No Aplica' : ($prestamo->fiador->cargas_familiares ?? 'No ingresada') }}
                </td>
            </tr>
            <tr>
                <td colspan="4"><strong>No. INTEGRANTES DE SU NUCLEO FAMILIAR:</strong></td>
                <td>{{ $prestamo->fiador->estadoCivil == 'Soltero' ? 'No Aplica' : ($prestamo->fiador->integrantes_nucleo_familiar ?? 'No ingresada') }}
                </td>
            </tr>
            <tr>
                <td colspan="4"><strong>INGRESOS MENSUALES APROXIMADOS:</strong></td>
                <td>Q. {{ number_format($prestamo->fiador->ingresos_mensuales ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4"><strong>EGRESOS MENSUALES APROX:</strong></td>
                <td>Q. {{ number_format($prestamo->fiador->egresos_mensuales ?? 0, 2) }}</td>
            </tr>
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
