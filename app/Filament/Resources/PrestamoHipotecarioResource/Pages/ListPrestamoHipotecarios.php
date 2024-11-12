<?php

namespace App\Filament\Resources\PrestamoHipotecarioResource\Pages;

use App\Filament\Resources\PrestamoHipotecarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrestamoHipotecarios extends ListRecords
{
    protected static string $resource = PrestamoHipotecarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
