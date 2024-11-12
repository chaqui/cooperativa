<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InversionResource\Pages;
use App\Filament\Resources\InversionResource\RelationManagers;
use App\Models\Inversion;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InversionResource extends Resource
{
    protected static ?string $model = Inversion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('cliente')
                ->relationship('cliente', 'nombres')
                ->label('Cliente')
                ->required()
                ->rules('max:255'),
                TextInput::make('monto')
                    ->label('Monto')
                    ->prefix('Q')
                    ->numeric()
                    ->required()
                    ->rules('max:255'),
                TextInput::make('Interes')
                    ->label('Interes')
                    ->numeric()
                    ->required()
                    ->rules('max:255'),
                TextInput::make('plazo')
                    ->label('Plazo')
                    ->numeric()
                    ->required()
                    ->rules('max:255'),
                Select::make("tipo_plazo")
                    ->relationship('tipoPlazo', 'nombre')
                    ->label('Tipo de Plazo')
                    ->required(),
                Select::make("tipo_taza")
                    ->relationship('tipoTaza', 'nombre')
                    ->label('Tipo de Taza')
                    ->required(),
                TextInput::make('fecha_inicio')
                    ->label('Fecha de Inicio')
                    ->date()
                    ->required()
                    ->rules('max:255'),
                TextInput::make('fecha_fin')
                    ->label('Fecha de Fin')
                    ->date()
                    ->required()
                    ->rules('max:255'),
                Select::make('tipo_inversion')
                    ->relationship('tipoInversion', 'nombre')
                    ->label('Tipo de Inversion')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([])
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
            'index' => Pages\ListInversions::route('/'),
            'create' => Pages\CreateInversion::route('/create'),
            'edit' => Pages\EditInversion::route('/{record}/edit'),
        ];
    }
}