<?php

namespace App\Filament\Resources\EstadoInversionResource\Pages;

use App\Filament\Resources\EstadoInversionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEstadoInversion extends EditRecord
{
    protected static string $resource = EstadoInversionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
