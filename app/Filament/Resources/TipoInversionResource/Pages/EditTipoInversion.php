<?php

namespace App\Filament\Resources\TipoInversionResource\Pages;

use App\Filament\Resources\TipoInversionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoInversion extends EditRecord
{
    protected static string $resource = TipoInversionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
