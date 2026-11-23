<x-store-layout title="Finalizar compra | ZAZA STORE">
    <section class="container zaza-section">
        <h1 class="zaza-heading mb-4">Finalizar compra</h1>
        @if ($errors->any())
            <ul class="zaza-shop-notice d-block ps-5" role="alert">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif
        @if ($pending || $summary['items'])
            <div class="zaza-account-settings">
                <section class="zaza-account-panel">
                    <h2 class="h4 fw-bold mb-4">Datos de envío</h2>
                    <form id="checkout-form" method="post" action="{{ route('checkout.store') }}">
                        @csrf
                        <input type="hidden" name="checkout_token" value="{{ $token }}">
                        @if ($pending)
                            <p>{{ $shipping['nombre_completo'] }} · {{ $shipping['telefono'] }}</p>
                            <p>{{ $shipping['direccion'] }}, {{ $shipping['ciudad'] }}, {{ $shipping['departamento'] }}</p>
                            <p class="mb-0">Pedido <a href="{{ route('orders.result', $pending) }}">{{ $pending->referencia }}</a></p>
                            @foreach ($shipping as $name => $value)
                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                            @endforeach
                        @else
                            <x-auth-field name="nombre_completo" label="Nombre y apellidos" :value="$shipping['nombre_completo']" autocomplete="name" />
                            <x-auth-field name="telefono" label="Teléfono" type="tel" :value="$shipping['telefono']" autocomplete="tel" />
                            <x-auth-field name="direccion" label="Dirección de envío" :value="$shipping['direccion']" autocomplete="street-address" />
                            <x-auth-field name="ciudad" label="Ciudad" autocomplete="address-level2" />
                            <x-auth-field name="departamento" label="Departamento" autocomplete="address-level1" />
                        @endif
                    </form>
                </section>
                <section class="zaza-account-panel">
                    <h2 class="h4 fw-bold mb-3">Resumen del pedido</h2>
                    <ul class="zaza-checkout-lines list-unstyled mb-4">
                        @if ($pending)
                            @foreach ($pending->items as $item)
                                <li><span>{{ $item->nombre }} · {{ $item->talla }}</span><span>× {{ $item->cantidad }}</span></li>
                            @endforeach
                        @else
                            @foreach ($summary['items'] as $item)
                                <li>
                                    <span>{{ $item['name'] }} · {{ $item['size'] }} × {{ $item['quantity'] }}
                                        @if ($item['notice'])<small class="d-block">{{ $item['notice'] }}</small>@endif
                                    </span>
                                    <span>{{ $item['subtotal'] }}</span>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                    <p class="d-flex justify-content-between gap-3 fw-bold"><span>Total (IVA incluido)</span><span>{{ $total }}</span></p>
                    @if ($canPay)
                        <button form="checkout-form" type="submit" class="btn btn-light zaza-button w-100">Pagar con Mercado Pago</button>
                    @elseif ($pending)
                        <p class="mb-0">El plazo de la reserva terminó. Consulta el resultado de tu pedido.</p>
                    @else
                        <p class="mb-0">Revisa tu carrito para continuar.</p>
                    @endif
                </section>
            </div>
        @else
            <p>Tu carrito está vacío.</p>
            <a href="{{ route('catalog', 'ropa') }}">Explorar ropa</a>
        @endif
    </section>
</x-store-layout>
