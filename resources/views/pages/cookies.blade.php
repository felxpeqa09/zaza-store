<x-store-layout title="Cookies | ZAZA STORE" description="Información sobre cookies, sesión y preferencias de navegación en ZAZA STORE.">
    <article class="container zaza-section zaza-privacy" x-data>
        <h1 class="zaza-heading mb-4">Cookies</h1>
        <p>La tienda usa cookies esenciales para conservar la sesión y el carrito y proteger formularios. La preferencia que elijas en el aviso se guarda en este navegador.</p>
        <p>El mapa de ubicación se conecta con el proveedor cuando decides cargarlo o permites el contenido externo. Si está habilitado, reCAPTCHA se utiliza en el inicio de sesión para comprobar la seguridad del formulario.</p>
        <button class="btn btn-light zaza-button" type="button" @click="$store.cookieConsent.visible = true">Cambiar preferencias</button>
    </article>
</x-store-layout>
