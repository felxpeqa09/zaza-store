<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\UpdateProfileRequest;
use App\Models\Order;
use App\Services\UpdateAccountProfile;
use App\Support\Cop;
use App\View\ProductDisplay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $profile = $request->user();
        $orders = $profile->orders()->latest('id')->paginate(5)->through(fn (Order $order) => [
            'reference' => $order->referencia,
            'url' => route('orders.show', $order),
            'status' => str($order->estado)->replace('_', ' ')->lower()->ucfirst()->toString(),
            'total' => Cop::format($order->total),
            'date' => $order->created_at->format('d/m/Y'),
        ]);
        $favorites = $profile->wishlists()->whereHas('product', fn ($query) => $query->activo())
            ->with('product.images')->latest('id')->limit(4)->get()
            ->map(fn ($favorite) => ProductDisplay::card($favorite->product));

        return view('account.show', compact('profile', 'orders', 'favorites'));
    }

    public function edit(Request $request): View
    {
        return view('account.edit', ['profile' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request, UpdateAccountProfile $profiles): RedirectResponse
    {
        $profiles->update($request->user(), $request->validated(), $request->file('avatar'), $request->boolean('quitar_avatar'));

        return to_route('account.edit')->with('profile_status', 'Tus datos se guardaron.');
    }

    public function avatar(Request $request): StreamedResponse
    {
        $path = $request->user()->avatar;
        $disk = Storage::disk('local');
        abort_unless($path && str_starts_with($path, 'avatars/') && $disk->exists($path), 404);

        return $disk->response($path, headers: [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
