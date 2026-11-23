<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\Product;
use App\View\ProductDisplay;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request): JsonResponse|View
    {
        $term = trim($request->validated('q') ?? '');
        $query = Product::query()->activo()->with('images')->orderBy('nombre')->orderBy('id');

        if ($term === '') {
            $query->whereRaw('false');
        } else {
            $query->buscar($term);
        }

        if ($request->expectsJson()) {
            return response()->json(['results' => $query->limit(6)->get()->map(ProductDisplay::card(...))]);
        }

        $products = $query->paginate(12)->withQueryString()->through(ProductDisplay::card(...));

        return view('catalog.search', compact('term', 'products'));
    }
}
