<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <style>
        body { font-family: DejaVu Sans, DejaVuSans, Arial, Helvetica, sans-serif; font-size:12px; color:#222 }
        .report-header { background:#000; color:#fff; padding:10px 12px; }
        .report-subheader { background:#ffd800; color:#000; padding:8px 12px; margin-top:6px; }
        .tipo-info { background: #000; color: #fff; padding:6px 12px; margin-top:6px; }
        table { width:100%; border-collapse: collapse; margin-top:10px }
        th, td { border: 1px solid #e0e0e0; padding: 6px 8px; text-align: left; }
        th { background: #000; color: #fff; }
        .right { text-align: right; }
        tbody tr:nth-child(odd) { background: #fffbe6; }
        tbody tr:nth-child(even) { background: #fffefc; }
        .footer { margin-top:12px; font-size:11px; color:#666 }
    </style>
    <title>Reporte Cuenta Interna</title>
</head>
<body>
    <div class="report-header">
        <h2 style="margin:0;">Reporte Cuenta Interna</h2>
        <div style="font-size:12px; margin-top:4px">Generado: {{ now()->format('d-m-Y') }}</div>
    </div>

    <div class="report-subheader">
        <strong>Período:</strong>
        @if(!empty($from) || !empty($to))
            {{ $from ?? '...' }} - {{ $to ?? '...' }}
        @else
            Todos
        @endif
    </div>

    @if(!empty($tipo))
        <div class="tipo-info">
            <strong>Tipo cuenta interna ID:</strong> {{ $tipo['id'] ?? '' }} &nbsp;|
            <strong>Número de cuenta:</strong> {{ $tipo['numero_cuenta'] ?? '' }} &nbsp;|
            <strong>Banco:</strong> {{ $tipo['nombre_banco'] ?? '' }}
        </div>
    @endif

    <div style="margin-top:6px; font-weight:bold">
        Tipo de informe: {{ (isset($mode) && $mode === 'month') ? 'Mensual' : 'Anual' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha registro</th>
                <th>ID</th>
                <th>Descripción</th>
                <th class="right">Ingreso</th>
                <th class="right">Egreso</th>
                <th class="right">Saldo</th>
                <th class="right">Saldo general</th>
            </tr>
        </thead>
        <tbody>
            @php $runningTotal = 0; @endphp
            @foreach($rows as $r)
                @php
                    $rawDate = null;
                    if (is_array($r)) {
                        $rawDate = $r['created_at'] ?? $r['fecha'] ?? null;
                    } else {
                        $rawDate = $r->created_at ?? $r->fecha ?? null;
                    }
                    $formattedDate = '';
                    if ($rawDate) {
                        try {
                            $dt = \Carbon\Carbon::parse($rawDate);
                            $formattedDate = (isset($mode) && $mode === 'month') ? $dt->format('d') : $dt->format('d-m');
                        } catch (\Exception $e) {
                            $formattedDate = (string) $rawDate;
                        }
                    }

                    $id = is_array($r) ? ($r['id'] ?? '') : ($r->id ?? '');
                    $descripcion = is_array($r) ? ($r['descripcion'] ?? '') : ($r->descripcion ?? '');
                    $ingreso = is_array($r) ? ($r['ingreso'] ?? 0) : ($r->ingreso ?? 0);
                    $egreso = is_array($r) ? ($r['egreso'] ?? 0) : ($r->egreso ?? 0);
                    $saldo = is_array($r) ? ($r['saldo'] ?? null) : ($r->saldo ?? null);
                    $saldoFloat = (float) ($saldo ?? 0);
                    $runningTotal += $saldoFloat;
                @endphp
                <tr>
                    <td>{{ $formattedDate }}</td>
                    <td>{{ $id }}</td>
                    <td>{{ $descripcion }}</td>
                    <td class="right">{{ number_format($ingreso, 2) }}</td>
                    <td class="right">{{ number_format($egreso, 2) }}</td>
                    <td class="right">{{ isset($saldo) ? number_format($saldo, 2) : '' }}</td>
                    <td class="right">{{ number_format($runningTotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
