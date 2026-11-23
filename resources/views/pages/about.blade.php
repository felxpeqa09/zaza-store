<x-store-layout title="Quiénes somos | ZAZA STORE" description="Conoce ZAZA STORE y visita nuestra tienda de ropa urbana en el centro de Ibagué.">
    <section class="container zaza-section">
        <h1 class="zaza-heading mb-3">UBICACIÓN</h1>
        <p class="mb-4">Estamos ubicados en la {{ $address }}</p>
        @if ($map)
            <div class="zaza-map" x-data="{ show: false }">
                <template x-if="show || $store.cookieConsent.choice === 'external'">
                    <iframe src="{{ $map }}" title="Mapa de la tienda ZAZA STORE en Ibagué" loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </template>
                <button type="button" class="btn btn-light zaza-button" x-show="!show && $store.cookieConsent.choice !== 'external'" @click="show = true">Cargar mapa</button>
            </div>
        @endif
        <a class="d-inline-block mt-3" href="{{ $externalMap }}" rel="noopener noreferrer">Abrir en Google Maps</a>
    </section>
    <section class="zaza-about">
        <div class="container zaza-about-grid">
            <div>
                <h2 class="zaza-heading zaza-account-heading">Nuestra historia</h2>
                <p>En ZAZA STORE todo comenzó con una idea simple: crear una marca de ropa masculina.</p>
                <div class="zaza-why">
                    <h2 class="h5 fw-bold mb-2">¿Por qué elegirnos?</h2>
                    <p class="mb-0">En ZAZA STORE ofrecemos ropa de calidad, moderna y accesible, pensada para hombre.</p>
                </div>
            </div>
            <div class="zaza-about-image">
                <img src="{{ asset('img/zaza-editorial-modelos.webp') }}" alt="Modelos con ropa urbana" width="900" height="600" loading="lazy">
            </div>
        </div>
    </section>
</x-store-layout>
