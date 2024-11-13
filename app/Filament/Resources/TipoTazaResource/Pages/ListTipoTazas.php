<?php

namespace App\Filament\Resources\TipoTazaResource\Pages;

use App\Filament\Resources\TipoTazaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoTazas extends ListRecords
{
    protected static string $resource = TipoTazaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
