<?

namespace App\Services;

use App\Constants\Orientacion;

class OrientacionService
{
    public function obtenerOrientaciones()
    {
        return [
            Orientacion::PORTRAIT => 'Vertical (Portrait)',
            Orientacion::LANDSCAPE => 'Horizontal (Landscape)',
        ];
    }
}
