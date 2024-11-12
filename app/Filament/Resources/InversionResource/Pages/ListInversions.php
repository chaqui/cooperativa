<?php

namespace App\Filament\Resources\InversionResource\Pages;

use App\Filament\Resources\InversionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInversions extends ListRecords
{
    protected static string $resource = InversionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
