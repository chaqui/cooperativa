<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\WithoutEvents;
use App\Models\Prestamo_Hipotecario;
use App\Services\CuotaHipotecaService;
use App\Models\Pago;

class CuotaHipotecaServiceTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function testCalcularCuotasGeneraPagosCorrectamente()
    {
        // Crear un préstamo hipotecario de prueba
        $prestamoHipotecario = Prestamo_Hipotecario::factory()->create([
            'monto' => 100000,
            'interes' => 5,
            'plazo' => 12,
            'dpi_cliente' => '2222222222222',
            'fecha_inicio' => '2025-01-01',
            'tipo_plazo' => 2, // Asumiendo que 1 es mensual
        ]);

        // Crear una instancia del servicio
        $cuotaHipotecaService = new CuotaHipotecaService();

        // Llamar al método calcularCuotas
        $cuotaHipotecaService->calcularCuotas($prestamoHipotecario);

        // Verificar que se hayan generado los pagos correctos
        $pagos = Pago::where('id_prestamo', $prestamoHipotecario->id)->get();
        $this->assertCount(12, $pagos);

        // Verificar que los pagos tengan las propiedades correctas
        foreach ($pagos as $pago) {
            $this->assertEquals($prestamoHipotecario->id, $pago->id_prestamo);
            $this->assertFalse($pago->realizado);
            $this->assertNotNull($pago->fecha);
            $this->assertGreaterThan(0, $pago->capital);
            $this->assertGreaterThan(0, $pago->interes);
        }
    }
}
