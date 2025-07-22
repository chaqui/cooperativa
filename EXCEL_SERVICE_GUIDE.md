# PrestamoExcelService - Guía de Uso

## Descripción
El `PrestamoExcelService` permite generar archivos Excel con información de préstamos hipotecarios que incluye todos los campos según la imagen proporcionada.

## Características
- ✅ Genera archivo Excel con formato profesional
- ✅ Descarga directa desde el navegador
- ✅ Soporte para uno o múltiples préstamos
- ✅ Campos formateados (moneda, porcentaje, fechas)
- ✅ Validación de datos nulos
- ✅ Carga correcta de relaciones (asesor, cliente, propiedad, estado)

## Rutas API Disponibles

### 1. Descargar Excel de un préstamo específico
```
GET /api/excel/prestamo/{id}
```

### 2. Descargar Excel de múltiples préstamos
```
POST /api/excel/prestamos/multiple
Content-Type: application/json

{
    "prestamo_ids": [1, 2, 3, 4]
}
```

### 3. Descargar Excel de todos los préstamos
```
GET /api/excel/prestamos/all
```

### 4. Descargar Excel con filtros
```
GET /api/excel/prestamos/filtered?estado=3&fecha_inicio=2024-01-01&fecha_fin=2024-12-31
```

## Uso del Servicio

### Ejemplo básico
```php
use App\Services\PrestamoExcelService;
use App\Models\Prestamo_Hipotecario;

// Obtener el servicio
$excelService = app(PrestamoExcelService::class);

// Generar Excel de todos los préstamos
$excelData = $excelService->generateExcelAll();

// Generar Excel de un préstamo específico
$prestamo = Prestamo_Hipotecario::with(['asesor', 'cliente', 'propiedad', 'estado'])->find(1);
$excelData = $excelService->generateExcel($prestamo);

// Generar Excel de múltiples préstamos
$prestamos = Prestamo_Hipotecario::with(['asesor', 'cliente', 'propiedad', 'estado'])
    ->whereIn('id', [1, 2, 3])
    ->get();
$excelData = $excelService->generateExcel($prestamos);
```

### En un controlador
```php
public function descargarExcel($id)
{
    try {
        $prestamo = Prestamo_Hipotecario::with([
            'asesor', 'cliente', 'propiedad', 'estado'
        ])->findOrFail($id);
        
        $excelData = $this->prestamoExcelService->generateExcel([$prestamo]);
        
        return response($excelData['content'])
            ->withHeaders($excelData['headers']);
            
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
```

## Estructura del Excel Generado

El archivo Excel incluye las siguientes columnas:

| Columna | Campo | Descripción |
|---------|-------|-------------|
| A | No. de Asesor Financiero | Nombre del usuario que creó el préstamo |
| B | No. de Financiamiento | Código del préstamo |
| C | No. de Cliente | Código del cliente |
| D | Nombre del Cliente | Nombres + Apellidos |
| E | Teléfono | Teléfono del cliente |
| F | MONTO ORIGINAL | Monto del préstamo (formato moneda) |
| G | SALDO CAPITAL ACTUAL | Saldo pendiente (formato moneda) |
| H | INTERES MENSUAL | Tasa de interés (formato porcentaje) |
| I | GARANTIA | Tipo de garantía/propiedad |
| J | PLAZO (MESES) | Plazo en meses |
| K | DESTINO | Destino del préstamo |
| L | GENERO | Género del cliente |
| M | FECHA DE DESEMBOLSO | Fecha de inicio (DD/MM/YYYY) |
| N | FECHA DE FINALIZACION | Fecha de fin (DD/MM/YYYY) |
| O | ESTATUS | Estado actual del préstamo |
| P | Días de atraso | Días de morosidad |
| Q | CUOTA TOTAL | Cuota mensual (formato moneda) |

## Formato y Estilos

- **Encabezados**: Fondo amarillo, texto en negrita, centrado
- **Bordes**: Todas las celdas con borde delgado
- **Moneda**: Formato Q #,##0.00 para columnas F, G, Q
- **Porcentaje**: Formato 0.00% para columna H
- **Fechas**: Formato DD/MM/YYYY
- **Auto-ajuste**: Ancho automático de columnas

## Requisitos

- PhpSpreadsheet (instalar via composer)
- Laravel 8+
- Relaciones cargadas en los modelos

## Instalación de Dependencias

```bash
composer require phpoffice/phpspreadsheet
```

## Troubleshooting

### Error: "Property [asesor] does not exist on this collection instance"
**Solución**: Asegúrate de cargar las relaciones:
```php
$prestamos = Prestamo_Hipotecario::with(['asesor', 'cliente', 'propiedad', 'estado'])->get();
```

### Error: "Call to undefined method saldoPendiente()"
**Solución**: El método usa `method_exists()` para verificar si existe el método antes de llamarlo.

### Error: "Headers already sent"
**Solución**: El servicio usa `ob_start()` y `ob_get_clean()` para capturar la salida y retornarla como array para que el controlador maneje la respuesta HTTP.
