<?php

namespace App\Filament\Resources\TipoTazaInteresResource\Pages;

use App\Filament\Resources\TipoTazaInteresResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoTazaInteres extends ListRecords
{
    protected static string $resource = TipoTazaInteresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
