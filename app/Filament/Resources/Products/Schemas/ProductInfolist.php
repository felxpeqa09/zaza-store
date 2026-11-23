<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use App\View\ProductDisplay;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información')->columns(2)->columnSpanFull()->schema([
                TextEntry::make('nombre')->label('Nombre'),
                TextEntry::make('slug')->label('Slug'),
                TextEntry::make('precio_formateado')->label('Precio'),
                TextEntry::make('category.nombre')->label('Categoría'),
                TextEntry::make('subcategory.nombre')->label('Subcategoría')->placeholder('Sin subcategoría'),
                IconEntry::make('activo')->label('Activo')->boolean(),
                IconEntry::make('destacado')->label('Destacado')->boolean(),
                IconEntry::make('needs_price_review')->label('Revisar precio')->boolean(),
                TextEntry::make('descripcion')->label('Descripción')->columnSpanFull(),
            ]),
            ImageEntry::make('gallery')->label('Galería')->columnSpanFull()
                ->state(fn (Product $record) => $record->images()->pluck('ruta')->map(ProductDisplay::image(...))->all()),
            RepeatableEntry::make('variant_details')->label('Variantes')->columnSpanFull()->columns(3)
                ->state(fn (Product $record) => $record->variants()->with('color')->get()->map(fn ($variant) => [
                    'talla' => $variant->talla, 'color' => $variant->color?->nombre, 'stock' => $variant->stock,
                ])->all())
                ->schema([
                    TextEntry::make('talla')->label('Talla'),
                    TextEntry::make('color')->label('Color')->placeholder('Sin color'),
                    TextEntry::make('stock')->label('Stock disponible'),
                ]),
        ]);
    }
}
