<x-store-layout title="Mi pedido | ZAZA STORE">
    <section class="container zaza-section">
        <a class="d-inline-block mb-3" href="{{ route('account.show') }}">Volver a mi cuenta</a>
        <h1 class="zaza-heading zaza-account-heading mb-2">Mi pedido</h1>
        <p class="mb-4">{{ $order->referencia }} · {{ $status }}</p>
        @if ($currentStep !== false)
            <ol class="zaza-order-timeline list-unstyled mb-4" aria-label="Seguimiento del pedido">
                @foreach ($steps as $step => $label)
                    <li @if ($loop->index === $currentStep) aria-current="step" @endif @class(['zaza-step-reached' => $loop->index <= $currentStep])>
                        <span>{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        @endif
        <div class="zaza-account-settings">
            <section class="zaza-account-panel">
                <h2 class="h4 fw-bold mb-3">Información del pedido</h2>
                <dl class="mb-0">
                    <dt>Medio de pago</dt><dd>{{ $method ?? 'Por confirmar' }}</dd>
                    <dt>Forma de pago</dt><dd>{{ $type ?? 'Por confirmar' }}</dd>
                    <dt>Fecha de pago</dt><dd>{{ $order->fecha_pago?->format('d/m/Y H:i') ?? 'Por confirmar' }}</dd>
                    <dt>Entrega estimada</dt><dd class="mb-0">{{ $order->entrega_estimada?->format('d/m/Y') ?? 'Por confirmar' }}</dd>
                </dl>
            </section>
            <section class="zaza-account-panel">
                <h2 class="h4 fw-bold mb-3">Productos</h2>
                <ul class="zaza-checkout-lines list-unstyled mb-4">
                    @foreach ($items as $item)
                        <li>
                            <span>{{ $item['name'] }}<small class="d-block">{{ $item['color'] ? $item['color'].' · ' : '' }}Talla {{ $item['size'] }} · {{ $item['quantity'] }} un.</small></span>
                            <span>{{ $item['price'] }} <small>c/u</small></span>
                        </li>
                    @endforeach
                </ul>
                <p class="d-flex justify-content-between gap-2 fw-bold mb-0"><span>{{ $paid ? 'Total pagado' : 'Total del pedido' }}</span><span>{{ $total }}</span></p>
            </section>
        </div>
    </section>
</x-store-layout>
