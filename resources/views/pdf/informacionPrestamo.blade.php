<div class="section">
    <h2>Información del Préstamo</h2>
    <table>
        <tr>
            <th>Préstamo </th>
            <td colspan="3">{{ $prestamo->codigo }}</td>
        </tr>
        <tr>
            <th>Monto Total</th>
            <td>Q. {{ number_format($prestamo->monto, 2) }}</td>
            <th>Interés (Mensual)</th>
            <td>{{ $prestamo->interes }}%</td>
        </tr>
        <tr>
            <th>Cliente</th>
            <td>{{$prestamo->codigoCliente}}</td>
            <td colspan="2">{{ $prestamo->nombreCliente}}</td>
        </tr>
        <tr>
            <th>Fecha de Inicio</th>
            <td>{{ \Carbon\Carbon::parse($prestamo->fecha_inicio)->translatedFormat('d \d\e F \d\e Y') }}</td>
            <th>Fecha de Fin</th>
            <td>{{ \Carbon\Carbon::parse($prestamo->fecha_fin)->translatedFormat('d \d\e F \d\e Y') }}</td>
        </tr>

    </table>
</div>
