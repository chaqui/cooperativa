<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PrestamoExcelService;
use App\Models\Prestamo_Hipotecario;

class TestExcelGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:excel-generation {--limit=5}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la generación de Excel de préstamos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando prueba de generación de Excel...');

        try {
            $limit = $this->option('limit');

            // Cargar algunos préstamos con relaciones
            $prestamos = Prestamo_Hipotecario::with([
                'asesor', 'cliente', 'propiedad', 'estado'
            ])->take($limit)->get();

            $this->info("Cargados {$prestamos->count()} préstamos");

            // Verificar que las relaciones se cargaron correctamente
            foreach ($prestamos as $prestamo) {
                $this->line("Préstamo ID: {$prestamo->id}");
                $this->line("  - Asesor: " . ($prestamo->asesor ? $prestamo->asesor->name : 'Sin asesor'));
                $this->line("  - Cliente: " . ($prestamo->cliente ? ($prestamo->cliente->nombres . ' ' . $prestamo->cliente->apellidos) : 'Sin cliente'));
                $this->line("  - Propiedad: " . ($prestamo->propiedad ? $prestamo->propiedad->tipo : 'Sin propiedad'));
                $this->line("  - Estado: " . ($prestamo->estado ? $prestamo->estado->nombre : 'Sin estado'));
                $this->line("  - Fecha inicio: " . ($prestamo->getAttribute('fecha_inicio') ?? 'Sin fecha'));
                $this->line("  - Fecha fin: " . ($prestamo->getAttribute('fecha_fin') ?? 'Sin fecha'));
                $this->line("  - Monto: " . ($prestamo->monto ?? 0));
                $this->line("---");
            }

            // Intentar generar el Excel
            $excelService = app(PrestamoExcelService::class);
            $result = $excelService->generateExcel($prestamos);

            $this->info("✅ Excel generado exitosamente");
            $this->info("   Archivo: {$result['filename']}");
            $this->info("   Tamaño: " . strlen($result['content']) . " bytes");

        } catch (\Exception $e) {
            $this->error("❌ Error al generar Excel: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
