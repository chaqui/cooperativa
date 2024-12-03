<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InversionResource\Pages;
use App\Filament\Resources\InversionResource\RelationManagers;
use App\Models\Inversion;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
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
                Select::make('dpi_cliente')
                    ->relationship('cliente', 'nombres')
                    ->label('Cliente')
                    ->required()
                    ->rules('max:255'),
                TextInput::make('monto')
                    ->label('Monto')
                    ->prefix('Q')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                TextInput::make('interes')
                    ->label('Interes')
                    ->numeric()
                    ->postfix('%')
                    ->required()
                    ->rules('max:255'),
                Select::make("tipo_taza")
                    ->relationship('tipoTaza', 'nombre')
                    ->label('Tipo de Taza')
                    ->required(),
                TextInput::make('plazo')
                    ->label('Plazo')
                    ->numeric()
                    ->required()
                    ->rules('max:255'),
                Select::make("tipo_plazo")
                    ->relationship('tipoPlazo', 'nombre')
                    ->label('Tipo de Plazo')
                    ->required(),
                DatePicker::make('fecha')
                    ->label('Fecha de Inicio')
                    ->required(),
                Select::make('tipo_inversion')
                    ->relationship('tipoInversion', 'nombre')
                    ->label('Tipo de Inversion')
                    ->required(),
                Select::make('cuenta_recaudadora')
                    ->relationship('cuentaRecaudadora', 'numero_cuenta')
                    ->label('Cuenta Recaudadora')
                    ->required(),
                Select::make('id_estado')
                    ->relationship('estado', 'nombre')
                    ->label('Estado')
                    ->default('1')
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
                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto'),
                Tables\Columns\TextColumn::make('interes')
                    ->label('Interes'),
                Tables\Columns\TextColumn::make('plazo')
                    ->label('Plazo'),
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha de Inicio'),
                Tables\Columns\TextColumn::make('cliente.nombres')
                    ->label('Nombres del Cliente'),
                    Tables\Columns\TextColumn::make('cliente.apellidos')
                    ->label('Apellidos del Cliente'),
                Tables\Columns\TextColumn::make('estado.nombre')
                    ->label('Estado'),
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
            RelationManagers\PagosInversionRelationManager::class,
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
