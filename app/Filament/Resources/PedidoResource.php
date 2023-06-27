<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedidoResource\Pages;
use App\Filament\Resources\PedidoResource\RelationManagers;
use App\Models\Pedido;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Card;
use App\Models\Cliente;
use App\Models\Producto;
use Filament\Forms\Components\Select;
// Section
use Filament\Forms\Components\Section;
use Icetalker\FilamentStepper\Forms\Components\Stepper;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;


class PedidoResource extends Resource
{
    protected static ?string $model = Pedido::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        $total_venta = 0;
        return $form
            ->schema([

                Card::make()
                ->schema([
                    Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->user()->id),
                    Forms\Components\Select::make('cliente_id')
                    ->label('Cliente')
                        ->required()->options(Cliente::all()
                        ->pluck('nombre', 'id'))
                        ->preload()
                        ->searchable(),
                        Forms\Components\TextInput::make('numero_factura')
                        ->disabled()
                        ->default('OR-' . random_int(100000, 999999)),
                        Forms\Components\TextInput::make('observacion')
                            ->maxLength(255),
                    Forms\Components\DateTimePicker::make('fecha')
                        ->default(fn () => now())->disabled(),
                        Forms\Components\Select::make('estado_pedidos_id')
                        ->relationship('estado_pedidos', 'nombre')->default('pendiente')->default(1)
                        ->label('Estado'),
                        Forms\Components\TextInput::make('total_venta')
                        ->disabled(),
                        Placeholder::make('total')//how to add styles in tha component
                        ->reactive()
                        ->content(function ($get, $set) {
                            $sum = 0;
                            foreach ($get('productos') as $product) {
                            $sum = $sum + ($product['precio'] * $product['cantidad']);
                            }
                            $set('total_venta', $sum);
                            return $sum ;
                        })
                        ->extraAttributes(['class' => 'text-red-500 text-3xl']),
                Section::make('Productos')
                ->description('Agregar productos')
                ->collapsible()
                ->schema([
                    // products
                    Repeater::make('productos')
                            ->relationship()
                            ->schema([
                                Select::make('producto_id')
                                    ->label('Producto')
                                    ->options(Producto::all()->pluck('nombre', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set,callable $get) {
                                        $set('precio', Producto::find($state)?->precio ?? 0);
                                        $set('subtotal', Producto::find($state)?->precio ?? 0);
                                    })
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),

                                    Stepper::make('cantidad')
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->default(1)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                    $set('subtotal', $state * $get('precio'),
                                    ))
                                    ->columnSpan([
                                        'md' => 2,
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('precio')
                                    ->label('Precio Unitario')
                                    ->disabled()
                                    ->numeric()
                                    ->required()
                                    ->columnSpan([
                                        'md' => 2,
                                    ]),
                                    Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->reactive()
                                    ->numeric()
                                    ->required()
                                    ->columnSpan([
                                        'md' => 2,
                                    ]),
                            ])
                            ->orderable()
                            ->defaultItems(1)
                            ->disableLabel()
                            ->columns([
                                'md' => 10,
                            ])
                            ->required(),
                            ])
        ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_factura')->label('N°')->searchable(),
                Tables\Columns\TextColumn::make('estado_pedidos.nombre')->label('Estado'),
                Tables\Columns\TextColumn::make('users.name')->label('Vendedor'),
                Tables\Columns\TextColumn::make('clientes.nombre'),
                Tables\Columns\TextColumn::make('total_venta'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListPedidos::route('/'),
            'create' => Pages\CreatePedido::route('/create'),
            'view' => Pages\ViewPedido::route('/{record}'),
            'edit' => Pages\EditPedido::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
