<x-store-layout description="Ropa urbana masculina, zapatos y accesorios en Ibagué.">
    <p class="zaza-slogan mb-0">EL MUNDO ES TUYO, VISTELO A TU ESTILO</p>
    <section class="container zaza-hero">
        <div class="zaza-hero-copy">
            <h1 class="zaza-display mb-4">ZAZA<br>STORE</h1>
            <a class="btn btn-light zaza-button px-4 mb-4" href="{{ route('catalog', 'ropa') }}">Comprar ahora</a>
            <p class="mb-0">Bienvenido a ZAZA STORE. Aquí encuentras lo que te gusta, con estilo y buena vibra. ¡Explora, disfruta y hazlo tuyo!</p>
        </div>
        <img src="{{ asset('img/zaza-editorial-hero.webp') }}" alt="Modelos con ropa urbana bajo una estructura de concreto" width="1200" height="800" fetchpriority="high">
    </section>

    <section class="zaza-brand-hero">
        <div class="zaza-brand-hero-copy">
            <img class="zaza-brand-hero-logo" src="{{ asset('img/logo-circular-zaza-store.png') }}" alt="ZAZA STORE" width="1254" height="1254" loading="lazy">
            <h2 class="zaza-brand-hero-heading mb-0">Más que una marca,<br>es un sueño hecho realidad.</h2>
        </div>
        <div class="zaza-brand-hero-photo">
            <img src="{{ asset('img/camisetas-estampadas-zaza-store.png') }}" alt="Dos modelos con camisetas estampadas de ZAZA STORE, una negra y otra blanca" width="1160" height="1355" loading="lazy">
        </div>
    </section>

    <section class="container zaza-section">
        <h2 class="zaza-heading mb-2">PRODUCTOS MÁS VENDIDOS</h2>
        <p class="mb-4">Descubre los productos favoritos por nuestros clientes</p>
        @if ($featured->isNotEmpty())
            <div class="zaza-product-grid zaza-featured-grid">
                @foreach ($featured as $item)
                    <x-product-card :product="$item" featured />
                @endforeach
            </div>
        @else
            <p class="mb-0">Pronto tendremos productos destacados.</p>
        @endif
    </section>
</x-store-layout>
