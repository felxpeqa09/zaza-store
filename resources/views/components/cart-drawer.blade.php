<aside id="cart-drawer" class="offcanvas offcanvas-end zaza-cart" tabindex="-1" aria-labelledby="cart-title"
    data-show-url="{{ route('cart.show') }}" data-items-url="{{ route('cart.items.store') }}"
    data-item-url="{{ route('cart.items.update', '__variant__') }}" x-data>
    <div class="offcanvas-header">
        <h2 class="zaza-heading offcanvas-title" id="cart-title">TU CARRITO</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar carrito"></button>
    </div>
    <div class="offcanvas-body">
        <p x-show="$store.cart.busy && !$store.cart.loaded" role="status" x-cloak>Cargando carrito…</p>
        <div x-show="$store.cart.error" x-cloak>
            <p role="alert" x-text="$store.cart.error"></p>
            <button x-show="!$store.cart.loaded" type="button" class="btn btn-outline-dark zaza-button" @click="$store.cart.refresh()">Volver a intentar</button>
        </div>
        <p x-show="$store.cart.loaded && !$store.cart.items.length" x-cloak>Tu carrito está vacío.</p>
        <ul class="list-unstyled mb-0">
            <template x-for="item in $store.cart.items" :key="item.id">
                <li class="zaza-cart-line">
                    <img :src="item.image" alt="" width="72" height="88" loading="lazy">
                    <div class="zaza-cart-line-copy">
                        <h3 class="h6 fw-bold mb-1" x-text="item.name"></h3>
                        <p class="small mb-1" x-text="[item.color, item.size && 'Talla ' + item.size, 'Cantidad ' + item.quantity].filter(Boolean).join(' · ')"></p>
                        <p class="small mb-1" x-show="item.price" x-text="item.price + ' por unidad'"></p>
                        <p class="fw-bold mb-1" x-show="item.subtotal" x-text="item.subtotal"></p>
                        <p class="small mb-1" x-show="item.notice" x-text="item.notice"></p>
                        <div class="d-flex align-items-center gap-2" x-show="$store.cart.editing !== item.id">
                            <button class="btn btn-link p-0" type="button" x-show="item.stock > 0" :disabled="$store.cart.busy" @click="$store.cart.edit(item)">EDITAR</button>
                            <button class="btn btn-link" type="button" :aria-label="'Eliminar ' + item.name + (item.size ? ', talla ' + item.size : '')"
                                :disabled="$store.cart.busy" @click="$store.cart.remove(item)">
                                <i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                        <form class="zaza-cart-edit" x-show="$store.cart.editing === item.id" @submit.prevent="$store.cart.update(item)">
                            <label class="visually-hidden" :for="'cart-quantity-' + item.id">Cantidad de <span x-text="item.name"></span></label>
                            <input class="form-control" :id="'cart-quantity-' + item.id" type="number" min="1" :max="item.stock" x-model.number="$store.cart.quantity" required>
                            <button type="submit" class="btn btn-dark zaza-button" :disabled="$store.cart.busy">Guardar</button>
                            <button type="button" class="btn btn-link p-0" @click="$store.cart.editing = null">Cancelar</button>
                        </form>
                    </div>
                </li>
            </template>
        </ul>
    </div>
    <div class="zaza-cart-totals" x-show="$store.cart.loaded" x-cloak>
        <dl class="mb-3">
            <div><dt>Total artículos</dt><dd x-text="$store.cart.units"></dd></div>
            <div><dt>Total a pagar <small>(IVA incluido)</small></dt><dd x-text="$store.cart.totalLabel"></dd></div>
        </dl>
        <a href="{{ route('checkout.show') }}" class="btn btn-dark zaza-button w-100">FINALIZAR COMPRA</a>
    </div>
</aside>
