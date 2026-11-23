<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class MergeGuestCart
{
    public function __construct(private CartService $cart, private Request $request) {}

    public function handle(Login $event): void
    {
        if ($event->guard === 'web' && $event->user instanceof User && $this->request->hasSession()) {
            $this->cart->mergeGuest($event->user, $this->request->session());
        }
    }
}
