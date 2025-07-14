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

        .client-info {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .client-info h4 {
            margin: 0 0 10px 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .receipt-number {
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 15px 0;
            border: 2px solid #007bff;
        }

        .receipt-number h3 {
            margin: 0;
            color: #007bff;
            font-size: 18px;
        }

        .deposit-info {
            background-color: #f0f8f0;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }

        .deposit-info h4 {
            margin: 0 0 10px 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .payment-info {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }

        .payment-info h4 {
            margin: 0 0 10px 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <div class="header">
            <img src="{{ base64Image('images/logoNegro.png') }}" alt="Logo" class="logo">
            <p style="margin: 5px 0; font-size: 14px;">Recibo de Depósito</p>
        </div>

        <div class="receipt-number">
            <h3>Número de Recibo: {{ $deposito->id }}</h3>
        </div>

        <div class="details">
            @php
                $cliente = null;
                if ($deposito->inversion && $deposito->inversion->cliente) {
                    $cliente = $deposito->inversion->cliente;
                } elseif ($deposito->pago && $deposito->pago->prestamo && $deposito->pago->prestamo->cliente) {
                    $cliente = $deposito->pago->prestamo->cliente;
                }
            @endphp

            @if($cliente)
            <div class="client-info">
                <h4>Información del Cliente</h4>
                <p><strong>DPI:</strong> {{ $cliente->dpi }}</p>
                <p><strong>Nombre:</strong> {{ $cliente->nombres }} {{ $cliente->apellidos }}</p>
                <p><strong>Teléfono:</strong> {{ $cliente->telefono }}</p>
                @if($cliente->nit)
                <p><strong>NIT:</strong> {{ $cliente->nit }}</p>
                @endif
            </div>
            @endif

            <div class="deposit-info">
                <h4>Información del Depósito</h4>
                <p><strong>Monto Depositado:</strong> Q{{ number_format($deposito->monto, 2) }}</p>
                <p><strong>Tipo Documento:</strong> {{$deposito->tipo_documento}}</p>
                <p><strong>Número de Documento:</strong> {{$deposito->numero_documento}}</p>
                <p><strong>Motivo:</strong> {{$deposito->motivo}}</p>
            </div>

            @if($deposito->pago && $deposito->pago->prestamo)
                <div class="payment-info">
                    <h4>Estado del Préstamo</h4>
                    <p><strong>Monto Total del Préstamo:</strong> Q{{ number_format($prestamo->monto, 2) }}</p>
                    <p><strong>Total Pagado:</strong> Q{{ number_format($totalPagado, 2) }}</p>
                    <p><strong>Saldo Pendiente:</strong> Q{{ number_format($montoPendiente, 2) }}</p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p><strong>Documento que confirma el depósito realizado</strong></p>
            <p style="font-size: 10px; margin-top: 15px;">
                Este recibo constituye comprobante oficial del depósito efectuado.
                Conserve este documento para sus registros contables.
            </p>
            <p style="font-size: 10px; color: #666;">
                Fecha de emisión: {{ Carbon::now()->translatedFormat('d \d\e F \d\e Y \a \l\a\s H:i') }}
            </p>
        </div>
    </div>
</body>

</html>
