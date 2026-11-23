@props(['product', 'featured' => false])
<article class="zaza-product-card">
    <a href="{{ $product['url'] }}" class="zaza-product-link">
        <img src="{{ $product['image'] }}" alt="" width="480" height="600" loading="lazy">
        <div class="zaza-product-copy">
            @if ($featured)
                <h3>{{ $product['name'] }}</h3>
            @else
                <h2>{{ $product['name'] }}</h2>
            @endif
            <p>{{ $product['price'] }}</p>
        </div>
    </a>
    <x-favorite-button :id="$product['id']" :name="$product['name']" class="zaza-card-favorite" />
    <a class="zaza-card-cart" href="{{ $product['url'] }}#seleccion" aria-label="Elegir variante de {{ $product['name'] }}">
        <i class="bi bi-bag-plus" aria-hidden="true"></i>
    </a>
</article>
