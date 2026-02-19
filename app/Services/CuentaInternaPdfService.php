<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
class CuentaInternaPdfService
{

   public function generarPdf($rows, $tipo, $anio = null, $mes = null)
    {
        $from = null;
        $to = null;
        if ($rows->count() > 0) {
            $first = $rows->first();
            $last = $rows->last();
            $from = $first->created_at ? $first->created_at->format('Y-m-d') : null;
            $to = $last->created_at ? $last->created_at->format('Y-m-d') : null;
        }

        $mode = $mes ? 'month' : 'year';

        $html = view('pdf.cunetaInterna', ['rows' => $rows, 'from' => $from, 'to' => $to, 'tipo' => $tipo, 'mode' => $mode])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();



        // Si no se proporcionó ruta, devolver el contenido binario
        return $dompdf->output();
    }
}
