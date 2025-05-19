<?

namespace App\Helpers;

use Illuminate\Support\Facades\Log;


class NumeroALetras
{
    /**
     * Convierte un número a letras, incluyendo decimales
     *
     * @param float|int $numero Número a convertir
     * @return string Número en letras
     * @throws \InvalidArgumentException Si el número no es válido
     */
    public static function convertir($numero): string
    {
        // Validar que el número sea válido
        if (!is_numeric($numero)) {
            throw new \InvalidArgumentException("El valor proporcionado no es un número válido.");
        }

        // Crear el formateador para convertir números a letras
        $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);

        // Separar la parte entera y decimal
        $entero = floor($numero);
        $decimal = round(($numero - $entero) * 100);

        // Convertir la parte entera a letras
        $letras = ucfirst($formatter->format($entero));
        $letras .= " quetzales";

        // Convertir la parte decimal a letras si existe
        if ($decimal > 0) {
            $letras .= " con " . $decimal . "/100 centavos";
        } else {
            $letras .= " exactos";
        }

        // Retornar el resultado
        return $letras;
    }
}
