<x-store-layout :title="$category->nombre.' | ZAZA STORE'" :description="'Explora '.$category->nombre.' en ZAZA STORE.'">
    <section class="container zaza-section">
        <div @class(['zaza-catalog-banner', 'zaza-catalog-banner--empty' => ! $bannerImage])>
            @if ($bannerImage)
                @if ($bannerLink)
                    <a href="{{ $bannerLink }}" aria-label="{{ $banner->texto ?: 'Ver promoción del catálogo' }}">
                @endif
                <img src="{{ $bannerImage }}" alt="" width="1440" height="480">
                @if ($bannerLink)
                    </a>
                @endif
            @endif
            <div class="zaza-banner-copy">
                <h1 class="zaza-heading mb-0">{{ $category->nombre }}</h1>
                @if ($banner?->texto)
                    <p class="mb-0">{{ $banner->texto }}</p>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <ul class="mt-3" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="get" action="{{ route('catalog', $category->slug) }}" class="zaza-catalog-controls">
            <details @if ($hasFilters) open @endif>
                <summary class="btn btn-outline-light zaza-button">FILTRAR +</summary>
                <div class="zaza-filters">
                    @if ($category->subcategories->isNotEmpty())
                        <label>Subcategoría
                            <select name="subcategoria" class="form-select">
                                <option value="">Todas</option>
                                @foreach ($category->subcategories as $subcategory)
                                    <option value="{{ $subcategory->slug }}" @selected(($filters['subcategoria'] ?? null) === $subcategory->slug)>{{ $subcategory->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <label>Precio desde (COP)
                        <input type="number" name="precio_min" class="form-control" min="0" value="{{ $filters['precio_min'] ?? '' }}">
                    </label>
                    <label>Precio hasta (COP)
                        <input type="number" name="precio_max" class="form-control" min="0" value="{{ $filters['precio_max'] ?? '' }}">
                    </label>
                    @if ($sizes)
                        <label>Talla
                            <select name="talla" class="form-select">
                                <option value="">Todas</option>
                                @foreach ($sizes as $size)
                                    <option value="{{ $size }}" @selected(($filters['talla'] ?? null) === (string) $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <a href="{{ route('catalog', $category->slug) }}">Limpiar filtros</a>
                </div>
            </details>
            <div class="zaza-sort">
                <label for="catalog-sort">Ordenar por</label>
                <select name="orden" id="catalog-sort" class="form-select">
                    @foreach (['recientes' => 'Más recientes', 'menor-precio' => 'Menor precio', 'mayor-precio' => 'Mayor precio', 'nombre' => 'Nombre'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['orden'] ?? 'recientes') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-light zaza-button">Aplicar</button>
            </div>
        </form>

        @if ($products->isNotEmpty())
            <div class="zaza-product-grid">
                @foreach ($products as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
            <x-pagination :products="$products" />
        @else
            <p>No encontramos productos con estos filtros.</p>
        @endif
    </section>
</x-store-layout>
