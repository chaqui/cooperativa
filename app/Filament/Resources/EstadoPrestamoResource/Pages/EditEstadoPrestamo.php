<?php

namespace App\Filament\Resources\EstadoPrestamoResource\Pages;

use App\Filament\Resources\EstadoPrestamoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEstadoPrestamo extends EditRecord
{
    protected static string $resource = EstadoPrestamoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
