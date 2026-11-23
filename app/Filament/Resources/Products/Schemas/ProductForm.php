<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Forms\CatalogImage;
use App\Models\Category;
use App\Models\Subcategory;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del producto')->columns(2)->columnSpanFull()->schema([
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(255)->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, ?string $old, Get $get, Set $set): void {
                        if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D'),
                Textarea::make('descripcion')->label('Descripción')->required()->maxLength(20000)->columnSpanFull(),
                TextInput::make('precio')->label('Precio (COP)')->required()->integer()->minValue(0),
                Select::make('category_id')->label('Categoría')->required()->live()
                    ->options(fn () => Category::query()->orderBy('nombre')->pluck('nombre', 'id'))
                    ->afterStateUpdated(fn (Set $set) => $set('subcategory_id', null)),
                Select::make('subcategory_id')->label('Subcategoría')
                    ->options(fn (Get $get) => Subcategory::query()->where('category_id', $get('category_id'))->orderBy('nombre')->pluck('nombre', 'id')),
                Toggle::make('activo')->label('Activo')->default(false),
                Toggle::make('destacado')->label('Destacado')->default(false),
                Toggle::make('needs_price_review')->label('Revisar precio')->default(false),
            ]),
            Section::make('Galería')->columnSpanFull()->schema([
                CatalogImage::make('gallery')->label('Imágenes')->multiple()->reorderable()->maxFiles(16)
                    ->panelLayout('grid')->helperText('JPG, PNG o WebP. Máximo 4 MB por imagen.'),
            ]),
            Section::make('Variantes e inventario')->columnSpanFull()->schema([
                Repeater::make('colors')->label('Colores')->defaultItems(0)->maxItems(24)->live()
                    ->addActionLabel('Añadir color')->columns(2)->schema([
                        Hidden::make('id'),
                        Hidden::make('key')->default(fn () => Str::uuid()->toString()),
                        TextInput::make('name')->label('Color')->required()->maxLength(255),
                        CatalogImage::make('image')->label('Imagen del color'),
                    ]),
                Repeater::make('variants')->label('Variantes')->defaultItems(0)->maxItems(160)
                    ->addActionLabel('Añadir variante')->reorderable(false)->columns(3)->schema([
                        Hidden::make('id'),
                        Hidden::make('expected_stock'),
                        Select::make('color_key')->label('Color')->placeholder('Sin color')
                            ->options(fn (Get $get): array => collect($get('../../colors') ?? [])
                                ->filter(fn ($row) => filled($row['key'] ?? null) && filled($row['name'] ?? null))
                                ->pluck('name', 'key')->all()),
                        TextInput::make('size')->label('Talla')->required()->maxLength(20)
                            ->helperText('Ropa: XS a XL. Calzado: número. Accesorios: Única.'),
                        TextInput::make('stock')->label('Stock disponible')->required()->integer()->minValue(0)->maxValue(2147483647),
                    ]),
            ]),
        ]);
    }
}
