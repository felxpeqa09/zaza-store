<header class="zaza-header">
    <nav class="container zaza-nav navbar navbar-expand-lg" aria-label="Navegación principal">
        <a class="zaza-brand d-inline-flex align-items-center" href="{{ route('home') }}">
            <x-store-logo />
        </a>
        <button class="navbar-toggler zaza-icon-button ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#store-navigation" aria-controls="store-navigation" aria-expanded="false" aria-label="Abrir navegación">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <div id="store-navigation" class="collapse navbar-collapse">
            <div class="zaza-nav-links navbar-nav">
                <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>INICIO</a>
                @foreach (['ropa' => 'ROPA', 'zapatos' => 'ZAPATOS', 'accesorios' => 'ACCESORIOS'] as $slug => $label)
                    <a href="{{ route('catalog', $slug) }}" @if (request()->routeIs('catalog') && request()->route('categoria') === $slug) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="zaza-nav-actions d-flex align-items-center">
            <x-live-search />
            <button type="button" class="zaza-icon-button zaza-cart-trigger" x-data
                aria-label="Abrir carrito" :aria-label="'Abrir carrito, ' + $store.cart.units + ' artículos'"
                aria-controls="cart-drawer" :aria-expanded="$store.cart.isOpen"
                @click="$store.cart.open($event.currentTarget)">
                <i class="bi bi-bag" aria-hidden="true"></i>
                <span class="zaza-cart-dot" x-show="$store.cart.units > 0" x-cloak aria-hidden="true"></span>
            </button>
            <a class="zaza-icon-button" href="{{ route('wishlist.index') }}" aria-label="Favoritos">
                <i class="bi bi-heart" aria-hidden="true"></i>
            </a>
            @guest
                <a class="zaza-icon-button zaza-account" href="{{ route('login') }}" aria-label="Iniciar sesión">
                    <i class="bi bi-person" aria-hidden="true"></i>
                </a>
            @else
                <a class="zaza-icon-button zaza-account" href="{{ route('account.show') }}" aria-label="Mi cuenta"
                    @if (request()->routeIs('account.*')) aria-current="page" @endif>
                    <i class="bi bi-person" aria-hidden="true"></i>
                </a>
            @endguest
        </div>
    </nav>
</header>
