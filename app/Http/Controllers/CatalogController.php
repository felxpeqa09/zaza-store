<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogRequest;
use App\Models\Banner;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Services\Catalog\BannerEditor;
use App\View\ProductDisplay;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __invoke(CatalogRequest $request, string $categoria): View
    {
        $category = Category::query()->with(['subcategories' => fn ($query) => $query->orderBy('nombre')])
            ->where('slug', $categoria)->firstOrFail();
        $filters = $request->validated();
        $hasFilters = collect($filters)->except('orden')->contains(fn ($value) => $value !== null);
        $query = $category->products()->activo()->with('images')
            ->filterCatalog($filters)->sortCatalog($filters['orden'] ?? null);

        $products = $query->paginate(12)->withQueryString()->through(ProductDisplay::card(...));

        $availableSizes = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->withoutTrashed()->activo()->porCategoria($category->id))
            ->distinct()->pluck('talla')->all();
        $sizes = array_values(array_filter(
            $categoria === 'ropa' ? ['XS', 'S', 'M', 'L', 'XL'] : $availableSizes,
            fn (string $size) => in_array($size, $availableSizes, true),
        ));
        if ($categoria === 'zapatos') {
            sort($sizes, SORT_NATURAL);
        }

        $banner = Banner::query()->where('activo', true)->orderBy('orden')->orderBy('id')->first();
        $bannerImage = $banner ? ProductDisplay::image($banner->imagen) : null;
        $bannerLink = BannerEditor::safeLink($banner?->enlace);

        return view('catalog.index', compact('category', 'filters', 'hasFilters', 'products', 'sizes', 'banner', 'bannerImage', 'bannerLink'));
    }
}
