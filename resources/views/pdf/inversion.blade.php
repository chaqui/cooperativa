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
        }

        .certificate-container {
            border: 2px solid #000;
            padding: 20px;
            width: auto;
            max-width: 650px;
            margin: 0 auto;
            box-sizing: border-box;
            text-align: center;
            position: relative;
        }

        .logo {
            width: 100px;
            height: auto;
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
        }
    </style>
</head>

<body>
    <div class="certificate-container">
        <div class="header">
            <img src="{{ base64Image('images/logoNegro.png') }}" alt="Logo" class="logo">
            <p>Certificado de Inversión</p>
        </div>
        <div class="content">
            <p><strong>Nombre del Inversionista:</strong> {{ $inversion->cliente->getFullNameAttribute() }}</p>
            <p><strong>Monto de la Inversión:</strong> Q{{ number_format($inversion->monto, 2) }}</p>
            <p><strong>Fecha de Inicio:</strong> {{ $inversion->fecha_inicio }}</p>
            <p><strong>Plazo: </strong> {{$inversion->plazo}} {{$inversion->tipoPlazo->nombre}}</strong></p>
            <p><strong>Número de Certificado:</strong> {{ $inversion->codigo }}</p>
        </div>
        <div class="footer">
            <p>Este certificado confirma la inversión realizada en COPECRECI bajo los términos y condiciones acordados.
            </p>
            <p>Gracias por confiar en nosotros.</p>
        </div>
    </div>
</body>

</html>
