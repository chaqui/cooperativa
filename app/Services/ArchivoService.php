<?

namespace App\Services;

use App\Traits\Loggable;

class ArchivoService
{
    use Loggable;
    /**
     * Guarda un archivo en el sistema de almacenamiento
     *
     * @param mixed $file Contenido del archivo o instancia de UploadedFile
     * @param string $path Ruta donde se guardará el archivo
     * @param string $fileName Nombre del archivo
     * @return string Ruta completa del archivo guardado
     * @throws \Exception Si ocurre un error al guardar el archivo
     */
    public function guardarArchivo($file, string $path, string $fileName): string
    {
        try {
            $this->log("Guardando archivo en la ruta: {$path} con el nombre: {$fileName}");

            // Verificar si la ruta existe, si no, crearla
            $fullPath = storage_path("app/{$path}");
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0777, true);
                $this->log("Ruta creada: {$fullPath}");
            }

            // Verificar si el archivo ya existe
            $filePath = $fullPath . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($filePath)) {
                $this->log("El archivo ya existe, se sobrescribirá: {$filePath}");
            } else {
                $this->log("El archivo no existe, se creará: {$filePath}");
            }

            // Guardar el archivo
            if (is_string($file)) {
                // Si el archivo es un contenido en forma de string
                file_put_contents($filePath, $file);
            } elseif ($file instanceof \Illuminate\Http\UploadedFile) {
                // Si el archivo es una instancia de UploadedFile
                $file->move($fullPath, $fileName);
            } else {
                throw new \Exception("El tipo de archivo no es válido. Debe ser un string o una instancia de UploadedFile.");
            }

            $this->log("Archivo guardado exitosamente en: {$filePath}");
            return $filePath;
        } catch (\Exception $e) {
            $this->logError("Error al guardar el archivo: " . $e->getMessage());
            throw new \Exception("Error al guardar el archivo: " . $e->getMessage(), 0, $e);
        }
    }

    public function obtenerArchivo($path)
    {

        if (file_exists($path)) {
            return file_get_contents($path);
        } else {
            throw new \Exception('El archivo no existe');
        }
    }
}
