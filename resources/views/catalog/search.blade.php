<x-store-layout title="Buscar productos | ZAZA STORE">
    <section class="container zaza-section">
        <h1 class="zaza-heading mb-4">Buscar productos</h1>
        <form class="d-flex gap-2 zaza-search-page-form mb-4" role="search" method="get" action="{{ route('search') }}">
            <label class="visually-hidden" for="search-query">Buscar productos</label>
            <input class="form-control" id="search-query" name="q" type="search" value="{{ $term }}" placeholder="Nombre del producto" maxlength="100" required>
            <button class="btn btn-light zaza-button">Buscar</button>
        </form>
        @if ($term === '')
            <p>Escribe el nombre del producto que buscas.</p>
        @elseif ($products->isEmpty())
            <p>No encontramos productos para «{{ $term }}».</p>
        @else
            <div class="zaza-product-grid">
                @foreach ($products as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
            <x-pagination :products="$products" />
        @endif
    </section>
</x-store-layout>
