<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Catalog\ProductEditor;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Product $product */
        $product = $this->getRecord();
        $product->load(['images', 'colors', 'variants']);

        return $data + [
            'gallery' => $product->images->pluck('ruta')->all(),
            'colors' => $product->colors->map(fn ($color) => [
                'id' => $color->id, 'key' => 'color-'.$color->id, 'name' => $color->nombre, 'image' => $color->imagen,
            ])->all(),
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => $variant->id, 'color_key' => $variant->product_color_id ? 'color-'.$variant->product_color_id : null,
                'size' => $variant->talla, 'stock' => $variant->stock, 'expected_stock' => $variant->stock,
            ])->all(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $saved = app(ProductEditor::class)->save($record, $data);
        $record->setRawAttributes($saved->getAttributes(), true);

        return $record;
    }

    protected function afterSave(): void
    {
        $this->refreshFormData(['gallery', 'colors', 'variants']);
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()->requiresConfirmation()];
    }
}
