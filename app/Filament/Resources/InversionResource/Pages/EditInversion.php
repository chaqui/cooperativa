<?php

namespace App\Filament\Resources\InversionResource\Pages;

use App\Filament\Resources\InversionResource;
use Filament\Actions;
use App\Models\Inversion;
use App\Services\CuotaService;
use Filament\Resources\Pages\EditRecord;

class EditInversion extends EditRecord
{
    protected static string $resource = InversionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('Calcular Rendimiento')
                ->action(function (array $data, Inversion $record): void {
                    $cuotaService = new CuotaService();
                    $cuotaService->calcularCuotaInversion($record);
                    $this->fillForm();
                }),
        ];
    }
}
