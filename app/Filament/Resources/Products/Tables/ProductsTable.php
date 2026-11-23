<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Category;
use App\Models\Product;
use App\View\ProductDisplay;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'images'])->withSum('variants as available_stock', 'stock'))
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('thumbnail')->label('Imagen')->state(fn (Product $record) => ProductDisplay::image($record->images->first()?->ruta)),
                TextColumn::make('nombre')->label('Nombre')->searchable(['nombre', 'slug'])->sortable(),
                TextColumn::make('category.nombre')->label('Categoría'),
                TextColumn::make('precio_formateado')->label('Precio')->sortable(['precio']),
                TextColumn::make('available_stock')->label('Stock disponible')->default(0)->sortable(),
                IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('Categoría')->options(fn () => Category::query()->orderBy('nombre')->pluck('nombre', 'id')),
                TernaryFilter::make('activo')->label('Activo'),
                TernaryFilter::make('destacado')->label('Destacado'),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()->requiresConfirmation()]);
    }
}
