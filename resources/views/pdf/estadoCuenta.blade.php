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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Cuenta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .logo {
            width: 100px;
            height: auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
        }

        .section {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ base64Image('images/logoNegro.png') }}" alt="Logo" class="logo">
        <h1>Estado de Cuenta</h1>
        <p>Fecha: {{ \Carbon\Carbon::now()->translatedFormat('d \d\e F \d\e Y')}}</p>

    </div>

    @include('pdf.informacionPrestamo', ['prestamo' => $prestamo])

    <div class="section">
        <h2>Pagos Realizados</h2>
        <table style="font-size:7px;">
            <thead>
                <tr>
                    <th># Cuota</th>
                    <th>Fecha a Pagar </th>
                    <th>Monto a Pagar</th>
                    <th>Interes a Pagar</th>
                    <th>Capital a Pagar</th>
                    <th>Penalizacion a Pagar</th>
                    <th>Fecha de Pago </th>
                    <th>Monto Pagado</th>
                    <th>Interes Pagado</th>
                    <th>Capital Pagado</th>
                    <th>Penalizacion Pagada</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pagos->sortBy('numero_pago_prestamo') as $payment)
                    <tr>
                        <td>{{ $payment->numero_pago_prestamo }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->fecha)->format('d/m/Y') }}</td>
                        <td>Q. {{ number_format($payment->monto(), 2) }}</td>
                        <td>Q. {{ number_format($payment->interes, 2) }}</td>
                        <td>Q. {{ number_format($payment->capital, 2) }}</td>
                        <td>Q. {{ number_format($payment->penalizacion, 2) }}</td>
                        <td>{{ $payment->fecha_pago ? \Carbon\Carbon::parse($payment->fecha_pago)->format('d/m/Y') : 'Pago no Realizado' }}
                        </td>
                        <td>Q. {{ number_format($payment->monto_pagado, 2) }}</td>
                        <td>Q. {{ number_format($payment->interes_pagado, 2) }}</td>
                        <td>Q. {{ number_format($payment->capital_pagado, 2) }}</td>
                        <td>Q. {{ number_format($payment->recargo, 2) }}</td>
                        <td>Q.
                            {{ number_format(($payment->nuevo_saldo && $payment->nuevo_saldo > 0) ? $payment->nuevo_saldo : $payment->saldo, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @include('pdf.resumenEstadoCuenta', ['prestamo' => $prestamo])
</body>

</html>
