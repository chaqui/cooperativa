@php
    use Carbon\Carbon;
    Carbon::setLocale('es');

    if (!function_exists('base64Image')) {
        function base64Image($path)
        {
            $fullPath = storage_path("app/public/" . $path);
            $type = pathinfo($fullPath, PATHINFO_EXTENSION);
            $data = file_get_contents($fullPath);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    }
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Depósito</title>
    <style>
        @page {
            size: letter;
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            max-width: 100%;
            margin: 0 auto;
            padding: 10mm;
            background: #fff;
            color: #000;
        }

        .logo {
            width: 80px;
            height: auto;
            display: block;
            margin: 0 auto 10px;
        }

        .receipt-container {
            width: 100%;
            max-height: 50%;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .title {
            font-size: 12px;
            font-weight: bold;
            margin: 4px 0;
        }

        .receipt-number {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            padding: 4px 0;
            border: 1px solid #000;
            margin-bottom: 8px;
            background-color: #f5f5f5;
        }

        .section {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ccc;
        }

        .section-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
            color: #333;
        }

        .row {
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 4px;
        }

        .amount-box {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            padding: 4px;
            border: 1px solid #000;
            margin: 6px 0;
            background-color: #f9f9f9;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .payment-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #ddd;
        }

        .payment-table td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            border-top: 2px solid #000;
            padding-top: 15px;
            margin-top: 20px;
            font-size: 11px;
        }

        .footer p {
            margin: 4px 0;
        }

        .cut-line {
            border-top: 1px dashed #000;
            margin-top: 25px;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <div class="header">
            <img src="{{ base64Image('images/logoNegro.png') }}" alt="Logo" class="logo">
            <div class="title">RECIBO DE DEPOSITO</div>
        </div>

        <div class="receipt-number">
            RECIBO No. {{ $deposito->id }}
        </div>

        @if($cliente)
            <div class="section" style="padding-bottom:2px;margin-bottom:2px;">
                <div class="row" style="margin-bottom:1px;">
                    <span style="font-weight:bold;">Cliente:</span>
                    {{ $cliente->nombres }} {{ $cliente->apellidos }} |
                    <span style="font-weight:bold;">Cod:</span> {{ $cliente->codigo }} |
                    <span style="font-weight:bold;">DPI:</span> {{ substr($cliente->dpi, 0, 4) }}-{{ substr($cliente->dpi, 4, 5) }}-{{ substr($cliente->dpi, 9, 4) }}
                </div>
            </div>
        @endif

        <div class="section">
            <div class="row" style="margin-bottom:1px;">
                <span style="font-weight:bold;">Depósito</span>
            </div>
            <div class="amount-box">Q{{ number_format($deposito->monto, 2) }}</div>
            <div class="row">
                <span style="font-weight:bold;">Tipo:</span> {{ $deposito->tipo_documento }} |
                <span style="font-weight:bold;">No. Doc:</span> {{ $deposito->numero_documento }}
            </div>
            <div class="row"><span style="font-weight:bold;">Motivo:</span> {{ $deposito->motivo }}</div>
        </div>

        @if($deposito->pago && $deposito->pago->prestamo)
            <div class="section">
                <div class="row" style="margin-bottom:1px;">
                    <span style="font-weight:bold;">Estado Préstamo</span>
                </div>
                <table class="payment-table">
                    <tr>
                        <td><span style="font-weight:bold;">Monto Préstamo</span></td>
                        <td>Q{{ number_format($prestamo->monto, 2) }}</td>
                    </tr>
                    <tr>
                        <td><span style="font-weight:bold;">Capital Pagado</span></td>
                        <td>Q{{ number_format($capitalPagado, 2) }}</td>
                    </tr>
                    <tr>
                        <td><span style="font-weight:bold;">Capital Pend.</span></td>
                        <td>Q{{ number_format($capitalPendiente, 2) }}</td>
                    </tr>
                    <tr>
                        <td><span style="font-weight:bold;">Interés a fecha</span></td>
                        <td>Q{{ number_format($interesPendiente, 2) }}</td>
                    </tr>
                </table>
            </div>
        @endif

        <div class="footer">
            <p><strong>Comprobante oficial</strong></p>
            <p>Conserve este documento</p>
        </div>

        <div class="cut-line">
            - - - - - - - - - - - - - - - - -
        </div>
    </div>
</body>

</html>
