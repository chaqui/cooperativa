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
    <title>Recibo de Retiro</title>
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

        .receipt-container {
            border: 2px solid #000;
            padding: 20px;
            width: auto;
            max-width: 650px;
            margin: 0 auto;
            box-sizing: border-box;
            position: relative;
        }

        .logo {
            width: 100px;
            height: auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }

        .header p {
            margin: 5px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .document-info {
            text-align: right;
            margin-bottom: 15px;
        }

        .document-info p {
            margin: 3px 0;
            font-size: 12px;
        }

        .content {
            margin: 20px 0;
        }

        .content-section {
            margin-bottom: 15px;
        }

        .content-section h3 {
            font-size: 14px;
            margin: 0 0 10px 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .content p {
            margin: 8px 0;
            font-size: 13px;
        }

        .amount {
            font-size: 16px;
            font-weight: bold;
        }

        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .signature {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
        }

        .footer {
            margin-top: 30px;
            font-size: 11px;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .transaction-number {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 14px;
            font-weight: bold;
            color: #444;
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <div class="transaction-number">No. {{ $retiro->id }}</div>

        <div class="header">
            <img src="{{ base64Image('images/logoNegro.png') }}" alt="Logo" class="logo">
            <p>RECIBO DE RETIRO</p>
        </div>

        <div class="document-info">
            <p><strong>Fecha:</strong> {{ Carbon::parse($retiro->created_at)->format('d/m/Y') }}</p>
            <p><strong>Hora:</strong> {{ Carbon::parse($retiro->created_at)->format('H:i:s') }}</p>
            <p><strong>No. Documento:</strong> {{ $retiro->numero_documento ?? 'N/A' }}</p>
        </div>

        <div class="content">
            <div class="content-section">


                <h3>DATOS DEL BENEFICIARIO</h3>
                <p><strong>Nombre:</strong>
                    @if($retiro->prestamo)
                        {{ $retiro->prestamo->cliente ? $retiro->prestamo->cliente->getFullNameAttribute() : 'No especificado' }}
                    @elseif($retiro->id_pago_inversions)
                        {{ $retiro->pagoInversion->inversion->cliente ? $retiro->pagoInversion->inversion->cliente->getFullNameAttribute() : 'No especificado' }}
                    @else
                        {{ $retiro->nombre_cliente ?? '__________________________' }}
                    @endif
                </p>
                <p><strong>Identificación:</strong>
                    @if($retiro->prestamo)
                        {{ $retiro->prestamo->cliente ? $retiro->prestamo->cliente->identificacion : 'No especificado' }}
                    @elseif($retiro->id_pago_inversions)
                        {{ $retiro->pagoInversion->inversion->cliente ? $retiro->pagoInversion->inversion->cliente->identificacion : 'No especificado' }}
                    @else
                        {{ $retiro->identificacion_cliente ?? '___________________________' }}
                    @endif
            </div>

            <div class="content-section">
                <h3>DATOS DEL RETIRO</h3>
                <p><strong>Monto:</strong> <span class="amount">Q{{ number_format($retiro->monto, 2) }}</span></p>
                <p><strong>Cuenta:</strong> {{ $retiro->tipoCuentaInterna->numero_cuenta ?? 'No especificada' }}</p>
                <p><strong>Motivo:</strong> {{ $retiro->motivo ?? 'No especificado' }}</p>
                @if($retiro->prestamo)
                    <p><strong>Préstamo asociado:</strong> {{ $retiro->prestamo->codigo ?? 'No especificado' }}</p>
                @elseif($retiro->id_pago_inversions)
                    <p><strong>Inversión asociada:</strong>
                        {{ $retiro->pagoInversion->inversion->codigo ?? 'No especificado' }}</p>
                @endif

                @if($retiro->id_pago_inversions)
                    <p><strong>Inversion asociada:</strong>
                        {{ $retiro->pagoInversion->inversion->codigo ?? 'No especificado' }}</p>
                @endif
                <p><strong>Estado:</strong> {{ $retiro->realizado ? 'Realizado' : 'Pendiente' }}</p>
            </div>
        </div>

        <div class="signatures">
            <div class="signature" style="text-align: center;">
                <div class="signature-line">
                    @if($retiro->prestamo)
                        {{ $retiro->prestamo->cliente ? $retiro->prestamo->cliente->getFullNameAttribute() : 'No especificado' }}

                    @elseif($retiro->id_pago_inversions)
                        {{ $retiro->pagoInversion->inversion->cliente ? $retiro->pagoInversion->inversion->cliente->getFullNameAttribute() : 'No especificado' }}
                    @else
                        Firma del Beneficiario

                    @endif
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Este recibo confirma la operación de retiro realizada en COPECRECI. El beneficiario declara haber
                recibido el monto indicado a su entera satisfacción.</p>
            <p>Para cualquier consulta relacionada con este retiro, por favor presente este recibo.</p>
        </div>
    </div>
</body>

</html>
