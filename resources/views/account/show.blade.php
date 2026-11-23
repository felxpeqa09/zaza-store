<x-store-layout title="Mi cuenta | ZAZA STORE">
    <div class="container zaza-section">
        <x-store-logo class="zaza-account-logo d-block mb-3" alt="" />
        <div class="zaza-account-header">
            <x-account-avatar :profile="$profile" />
            <div>
                <h1 class="zaza-heading mb-2">Mi cuenta</h1>
                <p class="h5 mb-0">{{ $profile->nombre_completo }}</p>
            </div>
            <a class="btn btn-light zaza-button" href="{{ route('account.edit') }}">CONFIGURAR CUENTA</a>
        </div>
        <dl class="zaza-account-data mt-4 mb-0">
            <div><dt>Correo electrónico</dt><dd>{{ $profile->email }}</dd></div>
            <div><dt>Teléfono</dt><dd>{{ $profile->telefono ?: 'Sin registrar' }}</dd></div>
            <div><dt>Género</dt><dd>{{ $profile->genero ?: 'Sin registrar' }}</dd></div>
            <div><dt>Dirección</dt><dd>{{ $profile->direccion ?: 'Sin registrar' }}</dd></div>
        </dl>
        @if (! $profile->hasVerifiedEmail())
            <a class="d-inline-block mt-3" href="{{ route('verification.notice') }}">Verificar correo electrónico</a>
        @endif
        @if ($profile->is_admin)
            <a class="d-block mt-3" href="{{ route('filament.admin.pages.dashboard') }}">Panel de administración</a>
        @endif

        <section class="mt-5" aria-labelledby="account-orders">
            <h2 class="zaza-heading zaza-account-heading mb-3" id="account-orders">Mis pedidos</h2>
            @forelse ($orders as $order)
                @if ($loop->first)
                    <ul class="zaza-orders list-unstyled mb-0">
                @endif
                <li>
                    <a class="fw-bold" href="{{ $order['url'] }}">{{ $order['reference'] }}</a>
                    <span>{{ $order['date'] }}</span>
                    <span>{{ $order['status'] }}</span>
                    <span>{{ $order['total'] }}</span>
                </li>
                @if ($loop->last)
                    </ul>
                @endif
            @empty
                <p>Aún no tienes pedidos.</p>
            @endforelse
            <x-pagination :products="$orders" />
        </section>

        <section class="mt-5" aria-labelledby="account-favorites">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="zaza-heading zaza-account-heading mb-0" id="account-favorites">Mis favoritos</h2>
                <a href="{{ route('wishlist.index') }}">Ver favoritos</a>
            </div>
            @if ($favorites->isNotEmpty())
                <div class="zaza-product-grid zaza-featured-grid">
                    @foreach ($favorites as $product)
                        <x-product-card :product="$product" featured />
                    @endforeach
                </div>
            @else
                <p>Aún no has guardado productos.</p>
            @endif
        </section>
        <form class="mt-5" method="post" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline-light zaza-button">Cerrar sesión</button>
        </form>
    </div>
</x-store-layout>
