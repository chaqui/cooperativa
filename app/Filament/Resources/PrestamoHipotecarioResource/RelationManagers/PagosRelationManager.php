<?php

namespace App\Filament\Resources\PrestamoHipotecarioResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;


class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('monto')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->dateTime(),

                Tables\Columns\TextColumn::make('monto')
                    ->prefix('Q'),
                Tables\Columns\TextColumn::make('fecha_pago')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('realizado')
                    ->formatStateUsing(function ($value) {
                        return $value ? 'Realizado' : 'Pendiente';
                    }),
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
