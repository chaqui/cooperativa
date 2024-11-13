<?php

namespace App\Filament\Resources\TipoTazaResource\Pages;

use App\Filament\Resources\TipoTazaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoTaza extends EditRecord
{
    protected static string $resource = TipoTazaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
