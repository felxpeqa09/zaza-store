@props(['id', 'name'])
@auth
    <button type="button" {{ $attributes->class('zaza-icon-button zaza-favorite') }}
        aria-label="Favorito: {{ $name }}"
        x-data="{ id: @js((string) $id) }" :aria-pressed="$store.wishlist.isSaved(id)"
        :disabled="!$store.wishlist.ready || $store.wishlist.pending.includes(id)" disabled
        @click="$store.wishlist.toggle(id)">
        <i class="bi bi-heart" :class="{ 'bi-heart-fill': $store.wishlist.isSaved(id), 'bi-heart': !$store.wishlist.isSaved(id) }" aria-hidden="true"></i>
    </button>
@else
    <a {{ $attributes->class('zaza-icon-button zaza-favorite') }} href="{{ route('wishlist.index') }}"
        aria-label="Iniciar sesión para guardar {{ $name }} en favoritos">
        <i class="bi bi-heart" aria-hidden="true"></i>
    </a>
@endauth
