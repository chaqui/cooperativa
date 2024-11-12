<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('dpi')
                    ->label('DPI')
                    ->placeholder('1234567890101')
                    ->required()
                    ->rules('max:13'),
                TextInput::make('nombres')
                    ->label('Nombres')
                    ->placeholder('Juan Carlos')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('apellidos')
                    ->label('Apellidos')
                    ->placeholder('Perez Perez')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('telefono')
                    ->label('Telefono')
                    ->placeholder('12345678')
                    ->required()
                    ->rules('max:8'),
                TextInput::make('correo')
                    ->label('Correo')
                    ->placeholder('juan.carlos@copecreci.com')
                    ->email(),
                TextInput::make('direccion')
                    ->label('Direccion')
                    ->placeholder('Ciudad, Zona, Calle, Casa')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('ciudad')
                    ->label('Ciudad')
                    ->placeholder('Ciudad')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('departamento')
                    ->label('Departamento')
                    ->placeholder('Departamento')
                    ->required()
                    ->rules('max:255'),
                Select::make('estado_civil')
                    ->label('Estado Civil')
                    ->placeholder('Seleccione una opcion')
                    ->options([
                        'Soltero' => 'Soltero',
                        'Casado' => 'Casado',
                        'Divorciado' => 'Divorciado',
                        'Viudo' => 'Viudo',
                    ])
                    ->required(),
                Select::make('genero')
                    ->label('Genero')
                    ->placeholder('Seleccione una opcion')
                    ->options([
                        'Masculino' => 'Masculino',
                        'Femenino' => 'Femenino',
                    ])
                    ->required(),
                Select::make('nivel_academico')
                    ->label('Nivel Academico')
                    ->placeholder('Seleccione una opcion')
                    ->options([
                        'Primaria' => 'Primaria',
                        'Basico' => 'Basico',
                        'Diversificado' => 'Diversificado',
                        'Universitario' => 'Universitario',
                        'Maestria' => 'Maestria',
                        'Doctorado' => 'Doctorado',
                    ])
                    ->required(),
                TextInput::make('profesion')
                    ->label('Profesion')
                    ->placeholder('Profesion')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('fecha_nacimiento')
                    ->label('Fecha de Nacimiento')
                    ->placeholder('YYYY-MM-DD')
                    ->required()
                    ->rules('date'),
                Select::make('etado_cliente')
                    ->label('Estado del Cliente')
                    ->placeholder('Seleccione una opcion')
                    ->relationship('estadoCliente', 'name')
                    ->default('1')
                    ->disableOptionWhen(function (string $value) {
                        return true;
                    }),
                TextInput::make('limite_credito')
                    ->label('Limite de Credito')
                    ->placeholder('1000.00')
                    ->required()
                    ->default('0.00')
                    ->rules('numeric'),
                TextInput::make('credito_disponible')
                    ->label('Credito Disponible')
                    ->placeholder('1000.00')
                    ->required()
                    ->default('0.00')
                    ->rules('numeric'),
                TextInput::make('ingresos_mensuales')
                    ->label('Ingresos Mensuales')
                    ->placeholder('1000.00')
                    ->required()
                    ->default('0.00')
                    ->rules('numeric'),
                TextInput::make('egresos_mensuales')
                    ->label('Egresos Mensuales')
                    ->placeholder('1000.00')
                    ->required()
                    ->default('0.00')
                    ->rules('numeric'),
                TextInput::make('capacidad_pago')
                    ->label('Capacidad de Pago')
                    ->placeholder('1000.00')
                    ->required()
                    ->default('0.00')
                    ->rules('numeric'),
                Select::make('calificacion')
                    ->label('Calificacion')
                    ->placeholder('Seleccione una opcion')
                    ->options([
                        'A' => 'A',
                        'B' => 'B',
                        'C' => 'C',
                        'D' => 'D',
                        'E' => 'E',
                    ])
                    ->required(),
                TextInput::make('fecha_actualizacion_calificacion')
                    ->label('Fecha de Actualizacion de Calificacion')
                    ->placeholder('YYYY-MM-DD')
                    ->required()
                    ->readOnly()
                    ->default(now()->format('Y-m-d'))
                    ->rules('date'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dpi')
                    ->label('DPI'),
                TextColumn::make('nombres')
                    ->label('Nombres'),
                TextColumn::make('apellidos')
                    ->label('Apellidos'),
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
            RelationManagers\CuentasBancariasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}