<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\View\ProductDisplay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $favorites = $request->user()->wishlists()->whereHas('product', fn ($query) => $query->activo());

        if ($request->expectsJson()) {
            return response()->json(
                ['ids' => $favorites->pluck('product_id')->map(fn ($id) => (string) $id)],
                headers: ['Cache-Control' => 'no-store'],
            );
        }

        $products = $favorites->with('product.images')->latest('id')->paginate(12)
            ->through(fn ($favorite) => ProductDisplay::card($favorite->product));

        $pageIds = collect($products->items())->pluck('id')->all();

        return view('wishlist.index', compact('products', 'pageIds'));
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->activo, 404);
        $request->user()->wishlists()->firstOrCreate(['product_id' => $product->id]);

        return response()->json(['saved' => true]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $request->user()->wishlists()->where('product_id', $product->id)->delete();

        return response()->json(['saved' => false]);
    }
}
