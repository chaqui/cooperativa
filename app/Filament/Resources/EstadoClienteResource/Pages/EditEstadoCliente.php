<?php

namespace App\Filament\Resources\EstadoClienteResource\Pages;

use App\Filament\Resources\EstadoClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEstadoCliente extends EditRecord
{
    protected static string $resource = EstadoClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
