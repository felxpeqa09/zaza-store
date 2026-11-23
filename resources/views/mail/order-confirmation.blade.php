<!doctype html>
<html lang="es">
<body>
    <h1>Pago confirmado</h1>
    <p>Recibimos el pago de tu pedido {{ $order->referencia }} en ZAZA STORE.</p>
    <p><a href="{{ route('orders.show', $order) }}">Ver mi pedido</a></p>
</body>
</html>
