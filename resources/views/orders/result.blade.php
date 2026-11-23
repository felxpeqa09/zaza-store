<x-store-layout title="Resultado del pago | ZAZA STORE">
    <section class="container zaza-section">
        <div class="zaza-account-panel zaza-order-result">
            <h1 class="zaza-heading zaza-account-heading mb-3">
                @if ($order->estado === 'PENDIENTE')
                    Estamos verificando tu pago
                @elseif ($order->fecha_pago)
                    Pago confirmado
                @else
                    {{ $status }}
                @endif
            </h1>
            <p>Pedido {{ $order->referencia }}</p>
            @if ($order->estado === 'PENDIENTE')
                <p>Si acabas de pagar, vuelve a consultar esta página en unos instantes.</p>
                <a class="btn btn-outline-light zaza-button me-2" href="{{ route('orders.result', $order) }}">Consultar estado</a>
            @elseif ($order->estado === 'EXPIRADO')
                <p>La reserva venció. Si ya pagaste, contacta a la tienda e indica la referencia de tu pedido.</p>
            @endif
            <a class="btn btn-light zaza-button" href="{{ route('orders.show', $order) }}">Ver mi pedido</a>
        </div>
    </section>
</x-store-layout>
