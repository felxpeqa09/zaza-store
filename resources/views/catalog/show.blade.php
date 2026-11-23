<x-store-layout :title="$product->nombre.' | ZAZA STORE'" :description="$product->descripcion" :open-graph="$openGraph">
    <section class="zaza-detail">
        <div class="container zaza-section">
            <a href="{{ route('catalog', $product->category->slug) }}" class="d-inline-flex align-items-center gap-2 mb-4">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>{{ $product->category->nombre }}
            </a>

            <div class="zaza-detail-grid" x-data="productSelection(@js($images), @js($colors), @js($variants))">
                <div>
                    <img class="zaza-detail-image" src="{{ $images[0] }}" :src="image" alt="{{ $product->nombre }}" width="720" height="900">
                    @if (count($images) > 1)
                        <ul class="zaza-thumbnails list-unstyled mt-3 mb-0" aria-label="Galería del producto">
                            @foreach ($images as $index => $image)
                                <li><button type="button" class="zaza-thumbnail" @click="image = @js($image)" :aria-pressed="image === @js($image)" aria-label="Ver foto {{ $index + 1 }}">
                                    <img src="{{ $image }}" alt="" width="64" height="72" loading="lazy">
                                </button></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="zaza-detail-copy" id="seleccion">
                    <x-favorite-button :id="$product->id" :name="$product->nombre" class="zaza-detail-favorite" />
                    <h1 class="zaza-heading mb-3">{{ $product->nombre }}</h1>
                    <p class="zaza-detail-price mb-4">{{ $product->precio_formateado }}</p>

                    @if ($colors->isNotEmpty())
                        <fieldset class="mb-4">
                            <legend>Color</legend>
                            <div class="zaza-thumbnails">
                                <template x-for="option in colors" :key="option.id">
                                    <button type="button" class="zaza-thumbnail" :aria-pressed="color === option.id" @click="chooseColor(option)">
                                        <template x-if="option.image"><img :src="option.image" alt="" width="48" height="52"></template>
                                        <span x-text="option.name"></span>
                                    </button>
                                </template>
                            </div>
                        </fieldset>
                    @endif
                    <fieldset class="mb-4">
                        <legend>Talla</legend>
                        <div class="zaza-thumbnails">
                            <template x-for="option in sizes" :key="option.id">
                                <button type="button" class="zaza-size" :aria-pressed="selected?.id === option.id"
                                    :disabled="option.stock < 1" @click="chooseSize(option)" x-text="option.size"></button>
                            </template>
                            <p x-cloak x-show="!sizes.length" class="mb-0">Sin tallas para esta selección.</p>
                        </div>
                    </fieldset>
                    <div class="mb-4">
                        <label for="quantity" class="d-block fw-bold mb-2">Cantidad</label>
                        <input class="form-control zaza-quantity" id="quantity" type="number" min="1" :max="selected?.stock ?? 1"
                            x-model.number="quantity" @change="limitQuantity()" :disabled="!selected" disabled>
                    </div>
                    <button type="button" class="btn btn-dark zaza-button px-4 mb-2" disabled
                        :disabled="!selected || $store.cart.busy"
                        @click="limitQuantity(); $store.cart.add(selected.id, quantity, $event.currentTarget)">AÑADIR AL CARRITO</button>
                    <p class="small mt-2 mb-4" x-show="$store.cart.error && !$store.cart.isOpen" x-cloak role="alert" x-text="$store.cart.error"></p>
                    <h2 class="h5 fw-bold">Descripción</h2>
                    <p class="zaza-description mb-0">{{ $product->descripcion }}</p>
                </div>
            </div>
        </div>
    </section>
</x-store-layout>
