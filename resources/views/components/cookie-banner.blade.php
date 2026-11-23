<section class="zaza-cookie-notice" aria-labelledby="cookie-notice-title" x-data
    x-show="$store.cookieConsent.visible" x-cloak>
    <div>
        <h2 class="h6 fw-bold mb-1" id="cookie-notice-title">Preferencias de cookies</h2>
        <p class="small mb-0">Usamos cookies esenciales para la sesión y el carrito. Puedes permitir el mapa externo. <a href="{{ route('cookies') }}">Más sobre cookies</a>.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-light zaza-button" @click="$store.cookieConsent.save('essential')">Solo esenciales</button>
        <button type="button" class="btn btn-light zaza-button" @click="$store.cookieConsent.save('external')">Permitir mapa externo</button>
    </div>
</section>
