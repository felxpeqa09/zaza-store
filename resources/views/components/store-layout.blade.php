@props(['title' => 'ZAZA STORE', 'description' => 'Tienda de ropa urbana masculina en Ibagué, Colombia.', 'openGraph' => null])
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($description)
        <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($description), 160) }}">
    @endif
    @if ($openGraph)
        @foreach ($openGraph as $property => $content)
            <meta property="og:{{ $property }}" content="{{ $content }}">
        @endforeach
        <link rel="canonical" href="{{ $openGraph['url'] }}">
    @endif
    <title>{{ $title }}</title>
    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('img/favicon-zaza-store.png') }}">
    @vite('resources/js/app.js')
</head>
<body class="d-flex flex-column">
    <a class="visually-hidden-focusable position-absolute p-2 bg-black" href="#contenido">Ir al contenido</a>
    <x-navbar />
    @auth
        <div id="wishlist-config" hidden data-list-url="{{ route('wishlist.index') }}"
            data-item-url="{{ route('wishlist.store', '__product__') }}"></div>
        <div class="container" x-data>
            <p class="visually-hidden" role="status" x-text="$store.wishlist.status"></p>
            <div class="zaza-shop-notice" x-show="$store.wishlist.error" x-cloak>
                <p class="mb-1" role="alert" x-text="$store.wishlist.error"></p>
                <button x-show="!$store.wishlist.ready" type="button" class="btn btn-link p-0" @click="$store.wishlist.init()">Volver a intentar</button>
            </div>
        </div>
    @endauth
    <div class="container" x-data>
        <div class="zaza-shop-notice" x-show="$store.cart.notices.length" x-cloak>
            <ul class="mb-0" role="status">
                <template x-for="notice in $store.cart.notices" :key="notice"><li x-text="notice"></li></template>
            </ul>
            <button type="button" class="btn btn-link p-0" @click="$store.cart.notices = []">Cerrar avisos</button>
        </div>
    </div>
    <main id="contenido" class="flex-grow-1">{{ $slot }}</main>
    <x-footer />
    <a class="zaza-whatsapp-link" href="{{ config('zaza.contact.whatsapp_url') }}" aria-label="Chatear con ZAZA STORE por WhatsApp">
        <i class="bi bi-whatsapp" aria-hidden="true"></i>
    </a>
    <x-cookie-banner />
    <x-cart-drawer />
</body>
</html>
