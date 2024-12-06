<?php

namespace App\Filament\Resources\PrestamoHipotecarioResource\Pages;

use App\Filament\Resources\PrestamoHipotecarioResource;
use App\Models\Prestamo_Hipotecario;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Services\CuotaHipotecaService;

class EditPrestamoHipotecario extends EditRecord
{
    protected static string $resource = PrestamoHipotecarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('Calcular Cuota')
                ->action(function (array $data, Prestamo_Hipotecario $record): void {
                    $cuotaService = new CuotaHipotecaService();
                    $cuotaService->calcularCuotas($record);
                    $this->fillForm();
                }),
        ];
    }
}
