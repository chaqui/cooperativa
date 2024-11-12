<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrestamoHipotecarioResource\Pages;
use App\Filament\Resources\PrestamoHipotecarioResource\RelationManagers;
use App\Models\Prestamo_Hipotecario;
use Faker\Provider\ar_EG\Text;
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
                TextInput::make('monto')
                    ->label('Monto')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('interes')
                    ->label('Interes')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('plazo')
                    ->label('Plazo')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('cuota')
                    ->label('Cuota')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('fecha_inicio')
                    ->label('Fecha de Inicio')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('fecha_fin')
                    ->label('Fecha de Fin')
                    ->required()
                    ->rules('max:255'),
                Select::make('cliente')
                    ->relationship('cliente', 'nombres')
                    ->label('Cliente')
                    ->required()
                    ->rules('max:255'),
                Select::make('propiedad')
                    ->relationship('propiedad', 'direccion')
                    ->createOptionForm(function () {
                        return [
                            TextInput::make('direccion')
                                ->label('Direccion')
                                ->required()
                                ->rules('max:255'),
                            TextInput::make('descripcion')
                                ->label('Descripcion')
                                ->required()
                                ->rules('max:255'),
                            TextInput::make('Valor_tasacion')
                                ->label('Valor de Tasacion')
                                ->required()
                                ->rules('max:255'),
                            TextInput::make('fecha_tasacion')
                                ->label('Fecha de Tasacion')
                                ->required()
                                ->rules('max:255'),
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
                Select::make('estado')
                    ->relationship('estado', 'nombre')
                    ->label('Estado')
                    ->required()
                    ->rules('max:255'),
                Select::make('tipo_taza')
                    ->relationship('tipoTaza', 'nombre')
                    ->label('Tipo de Taza')
                    ->required()
                    ->rules('max:255'),
                Select::make('tipo_plazo')
                    ->relationship('tipoPlazo', 'nombre')
                    ->label('Tipo de Plazo')
                    ->required()
                    ->rules('max:255'),
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
            //
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
