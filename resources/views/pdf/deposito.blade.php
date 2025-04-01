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
    <title>Recibo de Depósito</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .logo {
            width: 100px;
            height: auto;
        }


        .receipt-container {
            border: 1px solid #000;
            padding: 20px;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 20px;
        }

        .details {
            margin-bottom: 20px;
        }

        .details p {
            margin: 5px 0;
        }

        .amount {
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <div class="header">
            <img src="{{ base64Image('images/logoNegro.png') }}" alt="Logo" class="logo">
            <p style="margin: 5px 0; font-size: 14px;">Recibo de Depósito</p>
        </div>
        <div class="details">
            <p><strong>Número de Recibo:</strong> {{ $deposito->id }}</p>
        </div>
        <div class="amount">
            <p>Monto Depositado: Q{{ number_format($deposito->monto, 2) }}</p>
            <p>Tipo Documento: {{$deposito->tipo_documento}}</p>
            <p>Numero de Documento: {{$deposito->numero_documento}}</p>
            <p>Motivo: {{$deposito->motivo}}</p>
        </div>
        <div class="footer">
            <p>Documento que confirma el deposito</p>
        </div>
    </div>
</body>

</html>
