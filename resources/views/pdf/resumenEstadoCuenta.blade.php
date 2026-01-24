<div class="section">
    <h2>Resumen</h2>
    <table>

        <tr>
            <th>Interes Pagado</th>
            <td>Q. {{ number_format($prestamo->interesPagado, 2) }}</td>
        </tr>
        <tr>
            <th>Capital Pagado</th>
            <td>Q. {{ number_format($prestamo->capitalPagado, 2) }}</td>
        </tr>
        <tr>
            <th>Total Pagado</th>
            <td>Q. {{ number_format($prestamo->totalPagado, 2) }}</td>
        </tr>
        <tr>
            <th>Saldo Pendiente Capital</th>
            <td>Q. {{ number_format($prestamo->saldoPendienteCapital(), 2) }}</td>
        </tr>
        <tr>
            <th>Saldo Pendiente Intereses</th>
            <td>Q. {{ number_format($interes_pendiente, 2) }}</td>
        </tr>
        <tr>
            <th>Saldo Pendiente Penalización</th>
            <td>Q. {{ number_format($prestamo->saldoPendientePenalizacion(), 2) }}</td>
        </tr>
        <tr>
            <th>Total Saldo Pendiente</th>
            <td>Q. {{ number_format( $prestamo->saldoPendienteCapital() + $interes_pendiente + $prestamo->saldoPendientePenalizacion(), 2) }}</td>
        </tr>
    </table>
</div>
