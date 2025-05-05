<?

namespace App\Constants;


class InicialesCodigo
{
    // Definir las constantes primero
    public static $Prestamo_Hipotecario = 'prestamo_hipotecario';
    public static $Inversion = 'inversion';
    public static $Cliente = 'cliente';

    // Usar las constantes en el array estático
    private static $tipos = [
        'prestamo_hipotecario' => [
            'prefijo' => 'FCP',
            'secuencia' => 'correlativo_prestamo',
        ],
        'inversion' => [
            'prefijo' => 'ICP',
            'secuencia' => 'correlativo_inversion',
        ],
        'cliente' => [
            'prefijo' => 'CCP',
            'secuencia' => 'correlativo_cliente',
        ],
    ];

    /**
     * Obtiene la configuración del tipo de código
     *
     * @param string $tipo Tipo de código
     * @return array Configuración del tipo de código
     * @throws \Exception Si el tipo no es válido
     */
    public static function getTipo($tipo)
    {
        if (array_key_exists($tipo, self::$tipos)) {
            return self::$tipos[$tipo];
        }
        throw new \Exception("Tipo de código no válido: {$tipo}");
    }
}
