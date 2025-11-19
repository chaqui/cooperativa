@php
    use Carbon\Carbon;
    use App\Helpers\NumeroALetras;
    Carbon::setLocale('es');
    function base64Image($path)
    {
        // Intentar múltiples rutas
        $paths = [
            storage_path("app/public/" . $path),
            public_path($path),
            storage_path("app/" . $path),
            base_path("storage/app/public/" . $path)
        ];

        foreach ($paths as $fullPath) {
            if (file_exists($fullPath)) {
                $type = pathinfo($fullPath, PATHINFO_EXTENSION);
                $data = file_get_contents($fullPath);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        return ''; // Retorna vacío si no encuentra la imagen
    }
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Inversión</title>
    <style>
        @page {
            margin: 0cm;
            size: letter portrait;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            position: relative;
        }

        .numero {
            font-size: 15px;
            font-weight: bold;
            color: orange;
            position: relative;
            right: 20px;
            top: 20px;
            right: 20px;
            width: 100%;
            text-align: right;
        }

        .linea {
            font-size: 15px;
            font-weight: bold;
            background-color: #000;
            color: #fff;
            text-align: center;
            padding: 5px 0;
            margin: 20px 0;
            width: 100%;
        }

        .certificate-container {
            padding: 20px;
            width: auto;
            max-width: 650px;
            margin: 0 auto;
            box-sizing: border-box;
            text-align: center;
            position: relative;
        }

        .logo {
            width: 60px;
            height: 30px;
            max-width: 60px;
            max-height: 30px;
            object-fit: contain;
            display: block;
            margin: 5px auto;
            border: 1px solid #ccc; /* Para debug - ver el tamaño exacto */
        }

        .header {
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
        }

        .header p {
            margin: 5px 0;
            font-size: 14px;
        }

        .content {
            margin: 20px 0;
            text-align: left;
        }

        .content p {
            margin: 8px 0;
            font-size: 13px;
        }

        .footer {
            margin-top: 20px;
            font-size: 11px;
            text-align: justify;
        }
    </style>
</head>

<body>
    <div class="certificate-container" style="background-image: url('{{ base64Image("images/background.png") }}'); background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 500px;">
        <div class="header">
            <img src="{{ base64Image('images/logoNegro.png') }}" alt="Logo" class="logo">
        </div>
        <h1 class="numero"> NO. {{$inversion->codigo}}</h1>
        <p class="linea">CERTIFICADO DE DEPÓSITO DE INVERSIÓN A PLAZO FIJO</p>
        <div class="content">
            <p> Cooperación y Crecimiento S.A. extiende el presente CERTIFICADO DE DEPÓSITO DE INVERSIÓN A PLAZO FIJO a
                favor de:</p>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 25%;">
                        <strong>Cliente:</strong>
                    </td>
                    <td colspan="3" style="width: 75%;">
                        {{ $inversion->cliente->codigo }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 25%;">
                        <strong>Nombre:</strong>
                    </td>
                    <td colspan="3" style="width: 75%;">
                        {{ $inversion->cliente->getFullNameAttribute() }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 25%;">
                        <strong>CUI:</strong>
                    </td>
                    <td colspan="3" style="width: 75%;">
                        {{ $inversion->cliente->dpi }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 25%;">
                        <strong>La cantidad de:</strong>
                    </td>
                    <td colspan="3" style="width: 75%;">
                        Q{{ number_format($inversion->monto, 2) }}
                        ({{ NumeroALetras::convertir($inversion->monto) }})
                    </td>
                </tr>
                <tr>
                    <td style="width: 25%;">
                        <strong>Tasa de Interes:</strong>
                    </td>
                    <td style="width: 25%;">
                        {{ number_format($inversion->interes, 2) }} % anual
                    </td>
                    <td style="width: 25%;">
                        <strong>Plazo de Inversion:</strong>
                    </td>
                    <td style="width: 25%;">
                        {{ $inversion->plazo }} {{$inversion->tipoPlazo->nombre}}
                    </td>
                </tr>
                <tr>
                    <td style="width: 25%;">
                        <strong>Fecha de Inicio:</strong>
                    </td>
                    <td style="width: 25%;">
                        {{ Carbon::parse($inversion->fecha_inicio)->format('d-m-Y') }}
                    </td>
                    <td style="width: 25%;">
                        <strong>Fecha de Vencimiento:</strong>
                    </td>
                    <td style="width: 25%;">
                        {{ Carbon::parse($inversion->fecha)->format('d-m-Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 25%;">
                        <strong>Pago de Interes:</strong>
                    </td>
                    <td colspan="3" style="width: 75%;">
                        Mensual
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="width:100%">
                        <strong>Beneficiarios:</strong>
                    </td>
                </tr>
                @foreach ($beneficiarios as $beneficiario)
                    <tr>
                        <td style="width: 25%"></td>
                        <td style="width:25%">
                            {{ $beneficiario->nombre }}
                        </td>
                        <td style="width: 25%">
                            {{ $beneficiario->parentezco }}
                        </td>
                        <td style="width: 25%">
                            {{ $beneficiario->porcentaje }} %
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
        <div class="footer">
            <p>De acuerdo con lo solicitado hemos emitido un CERTIFICADO DE DEPÓSITO DE INVERSIÓN A PLAZO FIJO, por la
                suma y términos arriba indicados, dicho certificado NO ES TRANSFERIBLE y deberá presentarse
                personalmente al momento del cobro o renovaciÛn el mismo. Este depósito queda sujeto a las condiciones
                generales aprobadas por el CONSEJO DIRECTIVO y la política vigente por COPECRECI.
            </p>
            <br />
            <br />
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: center;">_____________________________</td>
                    <td></td>
                    <td style="text-align: center;">______________________________</td>
                    <td></td>
                </tr>
                <tr>
                    <td style="text-align: center;"><strong>firma y sello <br /> COPECRECI</strong></td>
                    <td></td>
                    <td style="text-align: center;"><strong>firma y huella <br /> CLIENTE</strong></td>
                    <td></td>
                </tr>
            </table>
        </div>
    </div>
    <div style="page-break-after: always;"></div>
    <div class="certificate-container" style="background-image: url('{{ base64Image("images/background.png") }}'); background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 500px;">
        <br />
        <br />
        <br />
        <br />
        <br />
        <p class="linea">ESTE CERTIFICADO ESTA SUJETO A LAS SIGUIENTES CONDICIONES:</p>
        <div class="content">
            <ul style="font-size: 13px; text-align: left;">
                <li style="text-align: justify;">Será pagado el día de su vencimiento mediante la entrega del presente
                    Certificado en original.</li>
                <br />
                <li style="text-align: justify;">Si este certificado no es presentado para su cancelación en la fecha de
                    su vencimiento, será prorrogable automáticamente por el mismo plazo a la tasa de interés vigente y
                    así sucesivamente.</li>
                <br />
                <li style="text-align: justify;">Si COPECRECI S.A., disminuye o aumenta la tasa de interés para los
                    depósitos de inversiones a plazo fijo, deberá dar aviso de las nuevas tasas para las aperturas y
                    renovaciones automáticas con noventa días de anticipación a la entrada en vigencia de las nuevas
                    tasas.</li>
                <br />
                <li style="text-align: justify;">En caso de que el depositante opte por retirarlo antes del vencimiento
                    del plazo, el depósito no devengará intereses por el lapso transcurrido entre el vencimiento y la
                    fecha de cancelación, como también quedará sujeto a un porcentaje de penalización establecido en
                    política vigente de COPECRECI.</li>
                <br />
                <li style="text-align: justify;">En caso de fallecimiento del titular, y si éste hubiese instituido
                    beneficiario (s), los fondos depositados serán entregados a éstos con sus respectivos intereses, en
                    las proporciones designadas por el titular, y en caso de que no la hiciera, se entenderá que la
                    distribución será por partes iguales. COPECRECI estará en la obligación de comunicar por escrito a
                    los beneficiarios, la designación que a su favor se hubiere hecho, dentro de los tres días
                    siguientes en que se tuviere conocimiento cierto del fallecimiento del cliente.</li>
                <br />
                <li style="text-align: justify;">Este Certificado será válido únicamente con la firma del Representante
                    Legal o Jefe de Agencia con su respectivo sello y también con la firma y/o huella del cliente.</li>
            </ul>
        </div>
    </div>
</body>

</html>
