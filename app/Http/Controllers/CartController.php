<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartItemRequest;
use App\Services\CartService;
use App\View\CartDisplay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function show(Request $request): JsonResponse
    {
        return $this->summary($request);
    }

    public function store(CartItemRequest $request): JsonResponse
    {
        $this->mergeGuest($request);
        $this->cart->add($request->user(), $request->session(), (int) $request->validated('variant_id'), (int) $request->validated('cantidad'));

        return $this->summary($request);
    }

    public function update(CartItemRequest $request, string $variant): JsonResponse
    {
        $this->mergeGuest($request);
        $this->cart->update($request->user(), $request->session(), $this->variantId($variant), (int) $request->validated('cantidad'));

        return $this->summary($request);
    }

    public function destroy(Request $request, string $variant): JsonResponse
    {
        $this->mergeGuest($request);
        $this->cart->remove($request->user(), $request->session(), $this->variantId($variant));

        return $this->summary($request);
    }

    private function mergeGuest(Request $request): void
    {
        if ($user = $request->user()) {
            $this->cart->mergeGuest($user, $request->session());
        }
    }

    private function summary(Request $request): JsonResponse
    {
        [$items, $variants] = $this->cart->snapshot($request->user(), $request->session());
        $notices = $request->session()->pull('cart_merge_notices', []);

        return response()->json(CartDisplay::summary($items, $variants, $notices), headers: ['Cache-Control' => 'no-store']);
    }

    private function variantId(string $id): int
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($id === false, 404);

        return $id;
    }
}
