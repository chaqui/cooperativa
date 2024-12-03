<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrestamoHipotecarioResource\Pages;
use App\Filament\Resources\PrestamoHipotecarioResource\RelationManagers;
use App\Models\Prestamo_Hipotecario;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrestamoHipotecarioResource extends Resource
{
    protected static ?string $model = Prestamo_Hipotecario::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('dpi_cliente')
                    ->relationship('cliente', 'nombres')
                    ->label('Cliente')
                    ->required(),
                TextInput::make('monto')
                    ->label('Monto')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->inputMode('decimal')
                    ->default(0)
                    ->prefix('Q'),
                TextInput::make('interes')
                    ->label('Interes')
                    ->required()
                    ->inputMode('decimal')
                    ->numeric()
                    ->postfix('%')
                    ->default(1)
                    ->minValue(1),
                Select::make('tipo_taza')
                    ->relationship('tipoTaza', 'nombre')
                    ->label('Tipo de Taza')
                    ->required(),
                TextInput::make('plazo')
                    ->label('Plazo')
                    ->required()
                    ->numeric(),
                Select::make('tipo_plazo')
                    ->relationship('tipoPlazo', 'nombre')
                    ->label('Tipo de Plazo')
                    ->required(),
                DatePicker::make('fecha_inicio')
                    ->label('Fecha de Inicio')
                    ->required(),

                Select::make('propiedad_id')
                    ->relationship('propiedad', 'Direccion')
                    ->createOptionForm(function () {
                        return [
                            TextInput::make('Direccion')
                                ->label('Direccion')
                                ->required()
                                ->rules('max:255'),
                            TextInput::make('Descripcion')
                                ->label('Descripcion')
                                ->required()
                                ->rules('max:255'),
                            TextInput::make('Valor_tasacion')
                                ->label('Valor de Tasacion')
                                ->required()
                                ->numeric()
                                ->minValue(0),
                            TextInput::make('Valor_comercial')
                                ->label('Valor Comercial')
                                ->required()
                                ->numeric()
                                ->minValue(0),
                            DatePicker::make('fecha_tasacion')
                                ->label('Fecha de Tasacion')
                                ->required(),
                            Select::make('tipo_propiedad')
                                ->label('Tipo de Propiedad')
                                ->placeholder('Seleccione una opcion')
                                ->relationship('tipoPropiedad', 'nombre')
                                ->createOptionForm(
                                    function () {
                                        return [
                                            TextInput::make('nombre')
                                                ->label('Nombre')
                                                ->required()
                                                ->rules('max:255'),
                                        ];
                                    }
                                )
                                ->required()
                                ->rules('max:255'),
                        ];
                    })
                    ->required(),
                Select::make('estado_id')
                    ->relationship('estado', 'nombre')
                    ->label('Estado')
                    ->default(1)
                    ->disableOptionWhen(function (string $value) {
                        return true;
                    })
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Id')
                    ->label('Id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('monto')
                    ->label('Monto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('interes')
                    ->label('Interes')
                    ->searchable()
                    ->sortable(),
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
            RelationManagers\PagosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrestamoHipotecarios::route('/'),
            'create' => Pages\CreatePrestamoHipotecario::route('/create'),
            'edit' => Pages\EditPrestamoHipotecario::route('/{record}/edit'),
        ];
    }
}
