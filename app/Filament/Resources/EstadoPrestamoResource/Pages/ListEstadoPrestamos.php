<?php

namespace App\Filament\Resources\EstadoPrestamoResource\Pages;

use App\Filament\Resources\EstadoPrestamoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEstadoPrestamos extends ListRecords
{
    protected static string $resource = EstadoPrestamoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
