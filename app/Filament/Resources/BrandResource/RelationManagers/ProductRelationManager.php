<?php

namespace App\Filament\Resources\BrandResource\RelationManagers;

use App\Models\Product;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductRelationManager extends RelationManager
{
    protected static string $relationship = 'product';

    protected static ?string $recordTitleAttribute = 'Products';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    Grid::make(3)->schema([
                        Card::make()->schema([

                            TextInput::make('name')->label('Product Name')->required()->unique(Product::class, 'slug', ignoreRecord: true)->reactive()->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                            TextInput::make('slug')->unique(Product::class, 'slug', ignoreRecord: true)->required()->disabled(),

                            RichEditor::make('description')->required()
                        ]),
                        Section::make('Image')->schema([
                            FileUpload::make('image')->required()
                        ])->columns(1),

                        Section::make('pricing')->schema([
                            Grid::make()->schema([
                                TextInput::make('amount')->numeric()->label('Product Amount')->required()->default(0)->reactive()->afterStateUpdated(
                                    function ($state, Closure $get, callable $set) {
                                        $discount = $get('discount');
                                        if ($state && $discount) {
                                            $discount_amount = $state - ($state * ($discount / 100));
                                            $set('discount_amount', $discount_amount);
                                        } else {
                                            $set('discount_amount', 0);
                                        }
                                    }
                                ),
                                TextInput::make('discount_amount')->numeric()->label('Discounted Amount')->default(0)->disabled(),
                                TextInput::make('discount')->numeric()->label('Discounted')->default(0)->reactive()->afterStateUpdated(
                                    function ($state, Closure $get, callable $set) {
                                        $amount = $get('amount');
                                        if ($state && $amount) {
                                            $discount_amount = $amount - ($amount * ($state / 100));
                                            $set('discount_amount', $discount_amount);
                                        } else {
                                            $set('discount_amount', 0);
                                        }
                                    }
                                ),
                            ]),
                        ]),
                    ])->columnSpan(2),
                    Grid::make(3)->schema([
                        Section::make('Association')->schema([
                            Select::make('brand_id')->relationship('brand', 'name'),
                            Select::make('category')->multiple()->relationship('category', 'name')
                        ])->columns(1),
                        Section::make('Status')->schema([
                            Toggle::make('is_visible')->helperText('This product will be hidden from all sales channels'),

                            Placeholder::make('created_at')->content(fn (Product $record): string => $record->created_at->diffForHumans())->hidden(fn (?Product $record) => $record === null),
                            Placeholder::make('updated_at')->content(fn (Product $record): string => $record->updated_at->diffForHumans())->hidden(fn (?Product $record) => $record === null),
                        ])->columns(1),
                    ])->columnSpan(1),

                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image'),
                TextColumn::make('name'),
                TextColumn::make('amount')->prefix('₹'),
                TextColumn::make('discount_amount')->prefix('₹'),
                TextColumn::make('discount')->suffix('%'),
                TextColumn::make('brand.name'),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
