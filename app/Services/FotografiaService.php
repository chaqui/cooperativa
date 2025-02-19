<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Traits\Loggable;


class FotografiaService
{

    use Loggable;


    /**
     * Method to upload a photo
     * @param $file binary file
     * @param $idCliente identifier of the client
     * @return string path of the photo
     * @throws \Exception if the file is invalid
     */
    public function uploadFotografia($file, $idCliente)
    {

        if (!$file->isValid() || !$file->isFile() || !in_array($file->extension(), ['jpg', 'jpeg', 'png', 'gif'])) {
            throw new \Exception('Invalid file type');
        }

        $filename = $idCliente . '_' . time() . '.' . $file->extension();

        $path = $file->storeAs('fotografias', $filename, 'public');

        $this->log('Uploaded photo: ' . $path);
        return $path;
    }

    /**
     * Delete a photo
     * @param mixed $path path of the photo
     * @return void
     */
    public function deleteFotografia($path)
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Get the photo
     * @param mixed $path path of the photo
     * @return string
     */
    public function getFotografia($path)
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->get($path);
        }

        throw new \Exception('File not found');
    }
}
