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

    // Ordenar depósitos por fecha
    usort($depositos, function($a, $b) {
        return strtotime($a['fecha']) - strtotime($b['fecha']);
    });
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
            font-size: 12px;
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
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Monto Pagado</th>
                    <th>Interes Pagado</th>
                    <th>Capital Pagado</th>
                    <th>Penalizacion Pagada</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($depositos as $deposito)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($deposito["fecha"])->translatedFormat('d \d\e F \d\e Y') }}</td>
                        <td>Q. {{ number_format($deposito["monto"], 2) }}</td>
                        <td>Q. {{ number_format($deposito["interes_pagado"], 2) }}</td>
                        <td>Q. {{ number_format($deposito["capital_pagado"], 2) }}</td>
                        <td>Q. {{ number_format($deposito["penalizacion_pagada"], 2) }}</td>
                        <td>Q.
                            {{ number_format($deposito["saldo"], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @include('pdf.resumenEstadoCuenta', ['prestamo' => $prestamo])
</body>

</html>
