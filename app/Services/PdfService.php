<?php
namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    public function generatePdf($html, $filename = 'document.pdf')
    {
        // Configurar opciones de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Courier');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('locale', 'es_ES');

        // Inicializar Dompdf con las opciones
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // (Opcional) Configurar el tamaño y la orientación del papel
        $dompdf->setPaper('A4', 'portrait');

        // Renderizar el HTML como PDF
        $dompdf->render();

        // Salida del PDF generado al navegador
        $dompdf->stream($filename, ['Attachment' => false]);
    }
}
