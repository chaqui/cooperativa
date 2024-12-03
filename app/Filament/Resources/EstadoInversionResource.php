<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstadoInversionResource\Pages;
use App\Filament\Resources\EstadoInversionResource\RelationManagers;
use App\Models\Estado_Inversion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EstadoInversionResource extends Resource
{
    protected static ?string $model = Estado_Inversion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre')
                    ->placeholder('Nombre')
                    ->required()
                    ->rules('max:255'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InversionRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEstadoInversions::route('/'),
            'create' => Pages\CreateEstadoInversion::route('/create'),
            'edit' => Pages\EditEstadoInversion::route('/{record}/edit'),
        ];
    }
}
