<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContratoResource\Pages;
use App\Filament\Resources\ContratoResource\RelationManagers;
use App\Models\Contrato;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;


class ContratoResource extends Resource
{
    protected static ?string $model = Contrato::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->required(),
                TextInput::make('numero_contrato')
                    ->label('Número de Contrato')
                    ->required(),
                Select::make('dpi_cliente')
                    ->label('Cliente')
                    ->relationship('cliente', 'dpi')
                    ->required(),
                Select::make('id_hipoteca')
                    ->label('Hipoteca')
                    ->relationship('hipoteca', 'id')
                    ->nullable(),
                Select::make('id_inversion')
                    ->label('Inversión')
                    ->relationship('inversion', 'id')
                    ->nullable(),
                Select::make('id_fiducia')
                    ->label('Fiducia')
                    ->relationship('fiducia', 'id')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha'),
                Tables\Columns\TextColumn::make('numero_contrato')
                    ->label('Número de Contrato'),
                Tables\Columns\TextColumn::make('cliente.dpi')
                    ->label('Cliente'),
                Tables\Columns\TextColumn::make('hipoteca.id')
                    ->label('Hipoteca'),
                Tables\Columns\TextColumn::make('inversion.id')
                    ->label('Inversión'),
                Tables\Columns\TextColumn::make('fiducia.id')
                    ->label('Fiducia'),
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
            'index' => Pages\ListContratos::route('/'),
            'create' => Pages\CreateContrato::route('/create'),
            'edit' => Pages\EditContrato::route('/{record}/edit'),
        ];
    }
}
