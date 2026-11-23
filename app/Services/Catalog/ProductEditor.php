<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductEditor
{
    public function save(?Product $record, array $data): Product
    {
        $record ? Gate::authorize('update', $record) : Gate::authorize('create', Product::class);
        $data = $this->validate($record, $data);
        $media = new MediaChanges('catalog/products');

        try {
            $data['gallery'] = array_map(fn ($file) => $media->stage($file, 'data.gallery'), $data['gallery'] ?? []);
            foreach ($data['colors'] as &$color) {
                $color['image'] = $media->stage($color['image'] ?? null, 'data.colors');
            }
            unset($color);
            [$product, $obsolete] = DB::transaction(fn () => $this->persist($record, $data, $media), 3);
        } catch (Throwable $error) {
            $media->undo();
            if ($error instanceof UniqueConstraintViolationException) {
                $this->invalid('slug', 'El slug ya está en uso o el producto cambió. Recarga el formulario.');
            }
            throw $error;
        }

        $media->delete($obsolete);

        return $product;
    }

    private function validate(?Product $record, array $data): array
    {
        $unique = Rule::unique('products', 'slug');
        if ($record) {
            $unique->ignore($record);
        }
        $subcategory = Rule::exists('subcategories', 'id')->where('category_id', $data['category_id'] ?? 0);
        $data += ['colors' => [], 'variants' => [], 'gallery' => []];

        $validation = Validator::make($data, [
            'nombre' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', 'max:255', $unique],
            'descripcion' => ['required', 'string', 'max:20000'],
            'precio' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', $subcategory],
            'activo' => ['required', 'boolean'],
            'destacado' => ['required', 'boolean'],
            'needs_price_review' => ['required', 'boolean'],
            'gallery' => ['array', 'max:16'],
            'gallery.*' => ['required'],
            'colors' => ['array', 'max:24'],
            'colors.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'colors.*.key' => ['required', 'string', 'max:50', 'distinct'],
            'colors.*.name' => ['required', 'string', 'max:255'],
            'variants' => ['array', 'max:160'],
            'variants.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'variants.*.color_key' => ['nullable', 'string', 'max:50'],
            'variants.*.size' => ['required', 'string', 'max:20'],
            'variants.*.stock' => ['required', 'integer', 'between:0,2147483647'],
            'variants.*.expected_stock' => ['nullable', 'integer', 'between:0,2147483647'],
        ]);
        if ($validation->fails()) {
            $messages = [];
            foreach ($validation->errors()->messages() as $key => $errors) {
                $messages['data.'.$key] = $errors;
            }
            throw ValidationException::withMessages($messages);
        }

        return $data;
    }

    private function persist(?Product $record, array $data, MediaChanges $media): array
    {
        $product = $record ? Product::query()->lockForUpdate()->findOrFail($record->id) : new Product;
        $product->fill(Arr::only($data, $product->getFillable()));
        $product->save();

        $gallery = $product->images()->pluck('ruta')->all();
        $colors = $product->colors()->get()->keyBy('id');
        $paths = [...$gallery, ...$colors->pluck('imagen')->filter()->all()];
        foreach ($data['gallery'] as $image) {
            $media->verify($image, $gallery, 'data.gallery');
        }
        $colorIds = $this->saveColors($product, $data['colors'], $colors, $media);
        $this->saveVariants($product, $data['variants'], $colorIds);

        $product->colors()->whereNotIn('id', array_values($colorIds))->delete();
        if ($gallery !== array_values($data['gallery'])) {
            $product->images()->delete();
            foreach (array_values($data['gallery']) as $index => $image) {
                $product->images()->create(['ruta' => $image, 'orden' => $index]);
            }
        }

        return [$product, array_diff($paths, [...$data['gallery'], ...array_column($data['colors'], 'image')])];
    }

    private function saveColors(Product $product, array $rows, Collection $existing, MediaChanges $media): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $color = empty($row['id']) ? $product->colors()->make() : $existing->get($row['id']);
            if (! $color) {
                $this->invalid('colors', 'Un color no pertenece al producto.');
            }
            $media->verify($row['image'], [$color->imagen], 'data.colors');
            $color->fill(['nombre' => $row['name'], 'imagen' => $row['image']])->save();
            $ids[$row['key']] = $color->id;
        }

        return $ids;
    }

    private function saveVariants(Product $product, array $rows, array $colors): void
    {
        $existing = $product->variants()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $reserved = OrderItem::query()->whereIn('product_variant_id', $existing->keys())
            ->whereHas('order', fn ($query) => $query->where('estado', 'PENDIENTE')->whereNotNull('stock_reserved_until'))
            ->selectRaw('product_variant_id, SUM(cantidad) AS units')->groupBy('product_variant_id')->pluck('units', 'product_variant_id');

        $desired = $this->variantData($product, $rows, $colors, $existing, $reserved);
        $retained = collect($desired)->pluck('id')->filter();
        foreach ($existing->except($retained->all()) as $variant) {
            if ($reserved->has($variant->id)) {
                $this->invalid('variants', 'No puedes retirar una variante con reservas pendientes.');
            }
            DB::table('cart_items')->where('product_variant_id', $variant->id)->delete();
            $variant->delete();
        }

        foreach ($desired as $item) {
            $variant = $existing->get($item['id']);
            if ($variant && ($variant->talla !== $item['talla'] || $variant->product_color_id !== $item['product_color_id'])) {
                DB::table('cart_items')->where('product_variant_id', $variant->id)->delete();
                $variant->update(['talla' => '~'.$variant->id]);
            }
        }

        foreach ($desired as $item) {
            $variant = $existing->get($item['id']) ?? $product->variants()->make();
            $variant->fill(Arr::except($item, 'id'))->save();
        }
    }

    private function variantData(Product $product, array $rows, array $colors, Collection $existing, Collection $reserved): array
    {
        $category = Category::findOrFail($product->category_id)->slug;
        $seen = [];
        $desired = [];
        foreach ($rows as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : null;
            $variant = $existing->get($id);
            $colorKey = $row['color_key'] ?? null;
            $color = $colorKey ? ($colors[$colorKey] ?? null) : null;
            $size = $row['size'];
            if (($id !== null && ! $variant) || ($colorKey && ! $color) || ! self::validSize($category, $size)) {
                $this->invalid('variants', 'Revisa el color y la talla de las variantes para la categoría seleccionada.');
            }
            $key = ($color ?? 'sin-color').':'.$size;
            if (isset($seen[$key])) {
                $this->invalid('variants', 'Una combinación de color y talla está repetida.');
            }
            $seen[$key] = true;
            $stock = $this->stock($variant, $row);
            if ($reserved->has($id) && ($variant->talla !== $size || $variant->product_color_id !== $color
                || (int) $reserved[$id] + $stock > 2147483647)) {
                $this->invalid('variants', 'El cambio de talla, color o stock es incompatible con una reserva pendiente.');
            }
            $desired[] = ['id' => $id, 'product_color_id' => $color, 'talla' => $size, 'stock' => $stock];
        }

        return $desired;
    }

    private function stock(?ProductVariant $variant, array $row): int
    {
        $stock = (int) $row['stock'];
        if (! $variant) {
            return $stock;
        }

        $expected = isset($row['expected_stock']) ? (int) $row['expected_stock'] : null;
        if ($stock === $expected) {
            return $variant->stock;
        }
        if ($variant->stock !== $expected) {
            $this->invalid('variants', 'El stock cambió. Recarga el producto antes de editar las cantidades.');
        }

        return $stock;
    }

    private static function validSize(string $category, string $size): bool
    {
        return match ($category) {
            'ropa' => in_array($size, ['XS', 'S', 'M', 'L', 'XL'], true),
            'zapatos' => (bool) preg_match('/^[1-9][0-9]{0,2}$/D', $size),
            'accesorios' => $size === 'Única',
            default => false,
        };
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages(['data.'.$field => $message]);
    }
}
