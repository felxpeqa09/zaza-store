<?php

namespace App\Models;

use App\Support\Cop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'precio',
        'category_id',
        'subcategory_id',
        'activo',
        'destacado',
        'needs_price_review',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'integer',
            'activo' => 'boolean',
            'destacado' => 'boolean',
            'needs_price_review' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('orden')->orderBy('id');
    }

    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeDestacado(Builder $query): Builder
    {
        return $query->where('destacado', true);
    }

    public function scopePorCategoria(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeBuscar(Builder $query, string $term): Builder
    {
        $literal = addcslashes($term, '\\%_');

        return $query->where('nombre', 'ILIKE', '%'.$literal.'%');
    }

    public function scopeFilterCatalog(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['subcategoria'] ?? null, fn (Builder $query, string $slug) => $query->whereHas('subcategory', fn (Builder $subcategory) => $subcategory->where('slug', $slug)))
            ->when(isset($filters['precio_min']), fn (Builder $query) => $query->where('precio', '>=', $filters['precio_min']))
            ->when(isset($filters['precio_max']), fn (Builder $query) => $query->where('precio', '<=', $filters['precio_max']))
            ->when($filters['talla'] ?? null, fn (Builder $query, string $size) => $query->whereHas('variants', fn (Builder $variant) => $variant->where('talla', $size)->where('stock', '>', 0)));
    }

    public function scopeSortCatalog(Builder $query, ?string $sort): Builder
    {
        $query = match ($sort) {
            'menor-precio' => $query->orderBy('precio'),
            'mayor-precio' => $query->orderByDesc('precio'),
            'nombre' => $query->orderBy('nombre'),
            default => $query->latest('created_at'),
        };

        return $query->orderByDesc('id');
    }

    public function getPrecioFormateadoAttribute(): string
    {
        return Cop::format($this->precio);
    }
}
