<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateErrorHandling extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'migrate:error-handling {--dry-run : Show what would be changed without making actual changes}';

    /**
     * The console command description.
     */
    protected $description = 'Migra todos los servicios para usar el trait ErrorHandler';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 Ejecutando en modo DRY-RUN - No se realizarán cambios');
        } else {
            $this->info('🚀 Migrando servicios para usar ErrorHandler...');
        }

        $servicesPath = app_path('Services');
        $serviceFiles = File::glob($servicesPath . '/*.php');

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($serviceFiles as $file) {
            $fileName = basename($file);

            // Saltar ArchivoService que ya está migrado
            if ($fileName === 'ArchivoService.php') {
                $this->warn("⏭️  Saltando {$fileName} - ya migrado");
                $skipped++;
                continue;
            }

            try {
                $result = $this->processServiceFile($file, $isDryRun);

                if ($result['modified']) {
                    $this->info("✅ {$fileName} - {$result['message']}");
                    $processed++;
                } else {
                    $this->warn("⏭️  {$fileName} - {$result['message']}");
                    $skipped++;
                }

            } catch (\Exception $e) {
                $this->error("❌ Error procesando {$fileName}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->info("📊 Resumen:");
        $this->line("   Procesados: {$processed}");
        $this->line("   Saltados: {$skipped}");
        $this->line("   Errores: {$errors}");

        if ($isDryRun && $processed > 0) {
            $this->newLine();
            $this->info("Para aplicar los cambios, ejecuta: php artisan migrate:error-handling");
        }
    }

    private function processServiceFile(string $filePath, bool $isDryRun): array
    {
        $content = File::get($filePath);
        $originalContent = $content;

        // Verificar si ya usa ErrorHandler
        if (strpos($content, 'use App\Traits\ErrorHandler;') !== false) {
            return ['modified' => false, 'message' => 'Ya usa ErrorHandler'];
        }

        // Verificar si tiene manejo de errores que migrar
        if (!$this->hasErrorHandlingToMigrate($content)) {
            return ['modified' => false, 'message' => 'No requiere migración'];
        }

        // Aplicar transformaciones
        $content = $this->addErrorHandlerTrait($content);
        $content = $this->replaceThrowStatements($content);
        $content = $this->replaceTryCatchBlocks($content);

        if (!$isDryRun && $content !== $originalContent) {
            File::put($filePath, $content);
            return ['modified' => true, 'message' => 'Migrado exitosamente'];
        }

        return ['modified' => $content !== $originalContent, 'message' => 'Listo para migrar'];
    }

    private function hasErrorHandlingToMigrate(string $content): bool
    {
        return strpos($content, 'throw new') !== false ||
               strpos($content, 'catch (') !== false ||
               strpos($content, '\Exception') !== false;
    }

    private function addErrorHandlerTrait(string $content): string
    {
        // Buscar si ya hay otros traits
        if (preg_match('/class\s+\w+[^{]*\{(\s*)(use\s+[^;]+;(\s*))*/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[0][1] + strlen($matches[0][0]);

            // Verificar si ya tiene el import
            if (strpos($content, 'use App\Traits\ErrorHandler;') === false) {
                // Agregar import después de otros imports
                $content = $this->addImportStatement($content);
            }

            // Agregar el trait
            $traitStatement = "    use ErrorHandler;\n\n";
            $content = substr_replace($content, $traitStatement, $insertPosition, 0);
        }

        return $content;
    }

    private function addImportStatement(string $content): string
    {
        // Buscar la última línea de imports
        $lines = explode("\n", $content);
        $lastImportIndex = -1;

        foreach ($lines as $index => $line) {
            if (preg_match('/^use\s+[^;]+;/', trim($line))) {
                $lastImportIndex = $index;
            } elseif (trim($line) === '' || strpos(trim($line), '//') === 0) {
                continue;
            } elseif ($lastImportIndex > -1) {
                break;
            }
        }

        if ($lastImportIndex > -1) {
            array_splice($lines, $lastImportIndex + 1, 0, ['use App\Traits\ErrorHandler;']);
        }

        return implode("\n", $lines);
    }

    private function replaceThrowStatements(string $content): string
    {
        // Reemplazar throw new \Exception simples
        $patterns = [
            // throw new \Exception("mensaje");
            '/throw new \\\\Exception\s*\(\s*["\']([^"\']+)["\']\s*\);/' => '$this->lanzarExcepcionConCodigo("$1");',

            // throw new Exception("mensaje");
            '/throw new Exception\s*\(\s*["\']([^"\']+)["\']\s*\);/' => '$this->lanzarExcepcionConCodigo("$1");',

            // throw new \InvalidArgumentException("mensaje");
            '/throw new \\\\InvalidArgumentException\s*\(\s*["\']([^"\']+)["\']\s*\);/' => '$this->lanzarExcepcionConCodigo("$1");',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function replaceTryCatchBlocks(string $content): string
    {
        // Buscar patrones de try-catch para simplificar
        $pattern = '/} catch \(\\\\?Exception \$e\) \{[^}]*throw new \\\\?Exception\([^;]+\);[^}]*\}/';

        $content = preg_replace_callback($pattern, function($matches) {
            $block = $matches[0];

            // Si el catch solo re-lanza la excepción, simplificar
            if (strpos($block, '$this->manejarError($e') === false) {
                return '} catch (\Exception $e) {
            $this->manejarError($e);
        }';
            }

            return $block;
        }, $content);

        return $content;
    }
}
