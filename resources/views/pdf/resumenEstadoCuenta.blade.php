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
            <th>Saldo Pendiente</th>
            <td>Q. {{ number_format($prestamo->saldoPendiente, 2) }}</td>
        </tr>
    </table>
</div>
