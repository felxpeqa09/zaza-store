<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\View\ProductDisplay;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Product::query()->activo()->destacado()->with('images')
            ->latest('id')->limit(4)->get()->map(ProductDisplay::card(...));

        return view('store', compact('featured'));
    }
}
