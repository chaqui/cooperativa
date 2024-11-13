<?php

namespace App\Filament\Resources\TipoPlazoResource\Pages;

use App\Filament\Resources\TipoPlazoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoPlazos extends ListRecords
{
    protected static string $resource = TipoPlazoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
