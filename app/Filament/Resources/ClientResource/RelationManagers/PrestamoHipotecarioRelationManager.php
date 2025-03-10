<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class PrestamoHipotecarioRelationManager extends RelationManager
{
    protected static string $relationship = 'prestamosHipotecarios';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('valor')
            ->columns([
                TextColumn::make('Id')
                ->label('Id')
                ->searchable()
                ->sortable(),
            TextColumn::make('monto')
                ->label('Monto')
                ->searchable()
                ->prefix('Q')
                ->sortable(),
            TextColumn::make('interes')
                ->label('Interes')
                ->suffix('%')
                ->searchable()
                ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
