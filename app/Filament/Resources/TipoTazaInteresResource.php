<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoTazaInteresResource\Pages;
use App\Filament\Resources\TipoTazaInteresResource\RelationManagers;
use App\Models\Tipo_Tasa_Interes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TipoTazaInteresResource extends Resource
{
    protected static ?string $model = Tipo_Tasa_Interes::class;

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
                Forms\Components\TextInput::make('valor')
                    ->label('Valor')
                    ->numeric()
                    ->postfix('%')
                    ->placeholder('Valor')
                    ->required()
                    ->minValue(0),
                Forms\Components\TextInput::make('descripcion')
                    ->label('Descripcion')
                    ->placeholder('Descripcion')
                    ->required()
                    ->rules('max:255'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTipoTazaInteres::route('/'),
            'create' => Pages\CreateTipoTazaInteres::route('/create'),
            'edit' => Pages\EditTipoTazaInteres::route('/{record}/edit'),
        ];
    }
}
