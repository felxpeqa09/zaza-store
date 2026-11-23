<x-store-layout title="Favoritos | ZAZA STORE">
    <section class="container zaza-section">
        <h1 class="zaza-heading mb-4">Favoritos</h1>
        @if ($products->isNotEmpty())
            <div class="zaza-product-grid" x-data>
                @foreach ($products as $item)
                    <div x-show="!$store.wishlist.ready || $store.wishlist.isSaved(@js($item['id']))">
                        <x-product-card :product="$item" />
                    </div>
                @endforeach
            </div>
            <p class="mt-3 mb-0" x-data x-cloak
                x-show="$store.wishlist.ready && !@js($pageIds).some(id => $store.wishlist.isSaved(id))" role="status">
                No quedan favoritos en esta página.
            </p>
            <x-pagination :products="$products" />
        @else
            <p>Aún no has guardado productos.</p>
            <a href="{{ route('catalog', 'ropa') }}">Explorar ropa</a>
        @endif
    </section>
</x-store-layout>
