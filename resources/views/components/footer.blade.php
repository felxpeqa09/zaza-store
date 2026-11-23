<footer class="zaza-footer py-4">
    <div class="container zaza-footer-grid">
        <div>
            <h2 class="h6 fw-bold mb-3">Información de interés</h2>
            <ul class="list-unstyled mb-0 d-grid gap-2">
                <li><a href="{{ route('about') }}">Quiénes somos</a></li>
                <li><a href="{{ route('returns') }}">Política de devolución, cambios y garantías</a></li>
                <li><a href="{{ route('privacy') }}">Política de privacidad</a></li>
                <li><a href="{{ route('cookies') }}">Cookies</a></li>
            </ul>
        </div>
        <div>
            <h2 class="h6 fw-bold mb-3">Información de contacto</h2>
            <address class="d-grid gap-2 mb-0">
                <span>{{ config('zaza.contact.address') }}</span>
                <a href="{{ config('zaza.contact.whatsapp_url') }}">WhatsApp {{ config('zaza.contact.whatsapp') }}</a>
                <a href="mailto:{{ config('zaza.contact.email') }}">{{ config('zaza.contact.email') }}</a>
                <a href="{{ config('zaza.contact.instagram_url') }}">{{ config('zaza.contact.instagram') }}</a>
            </address>
        </div>
    </div>
</footer>
