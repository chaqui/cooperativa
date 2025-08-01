<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixErrorHandling extends Command
{
    protected $signature = 'fix:error-handling {service?} {--all : Fix all services}';
    protected $description = 'Corrige específicamente los servicios que no se migraron correctamente';

    public function handle()
    {
        $servicesToFix = $this->getServicesToFix();

        if (empty($servicesToFix)) {
            $this->info('✅ No hay servicios que necesiten corrección');
            return;
        }

        foreach ($servicesToFix as $servicePath) {
            $this->fixService($servicePath);
        }
    }

    private function getServicesToFix(): array
    {
        $servicesPath = app_path('Services');
        $serviceFiles = File::glob($servicesPath . '/*.php');
        $servicesToFix = [];

        foreach ($serviceFiles as $file) {
            $content = File::get($file);

            // Buscar servicios con problemas específicos
            if ($this->hasProblems($content, basename($file))) {
                $servicesToFix[] = $file;
            }
        }

        return $servicesToFix;
    }

    private function hasProblems(string $content, string $fileName): bool
    {
        // Buscar nombres de clase malformados
        if (preg_match('/class\s+\w+Servi\s+/', $content)) {
            $this->warn("⚠️  {$fileName} tiene nombre de clase malformado");
            return true;
        }

        // Buscar llamadas a métodos que no existen
        if (strpos($content, 'lanzarExcepcionConCodigo') !== false &&
            strpos($content, 'use App\Traits\ErrorHandler;') === false) {
            $this->warn("⚠️  {$fileName} usa lanzarExcepcionConCodigo sin importar ErrorHandler");
            return true;
        }

        // Buscar throw new Exception sin reemplazar
        if (preg_match('/throw new \\\\?Exception\([^)]+\);/', $content) &&
            strpos($content, 'use App\Traits\ErrorHandler;') !== false) {
            $this->warn("⚠️  {$fileName} tiene throw statements sin migrar");
            return true;
        }

        return false;
    }

    private function fixService(string $filePath): void
    {
        $fileName = basename($filePath);
        $content = File::get($filePath);
        $originalContent = $content;

        $this->info("🔧 Corrigiendo {$fileName}...");

        // Corregir nombres de clase malformados
        $content = preg_replace(
            '/class\s+(\w+)Servi\s+([^{]*)\{\s*use\s+ErrorHandler;\s*use/',
            'class $1Service$2{
    use ErrorHandler;
    use',
            $content
        );

        // Corregir throws no migrados
        $patterns = [
            '/throw new \\\\Exception\s*\(\s*["\']([^"\']+)["\']\s*\);/' => '$this->lanzarExcepcionConCodigo("$1");',
            '/throw new Exception\s*\(\s*["\']([^"\']+)["\']\s*\);/' => '$this->lanzarExcepcionConCodigo("$1");',
            '/throw new \\\\InvalidArgumentException\s*\(\s*["\']([^"\']+)["\']\s*\);/' => '$this->lanzarExcepcionConCodigo("$1");',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Mejorar manejo de try-catch
        $content = preg_replace(
            '/} catch \(\\\\?Exception \$e\) \{\s*DB::rollBack\(\);\s*\$this->logError\([^)]+\);\s*throw new \\\\?Exception\([^;]+\);\s*\}/',
            '} catch (\Exception $e) {
            DB::rollBack();
            $this->manejarError($e);
        }',
            $content
        );

        if ($content !== $originalContent) {
            File::put($filePath, $content);
            $this->info("✅ {$fileName} corregido exitosamente");
        } else {
            $this->line("ℹ️  {$fileName} no necesitaba correcciones");
        }
    }
}
