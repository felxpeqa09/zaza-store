@props(['title'])
<x-store-layout :title="$title.' | ZAZA STORE'">
    <section class="zaza-auth-page">
        <div class="container">
            <div class="zaza-auth mx-auto p-4 p-md-5">
                <x-store-logo class="zaza-account-logo d-block mx-auto mb-3" alt="" />
                <h1 class="zaza-heading zaza-auth-title mb-4">{{ $title }}</h1>
                @if (session('status'))
                    <p class="zaza-form-status" role="status">{{ session('status') }}</p>
                @endif
                {{ $slot }}
            </div>
        </div>
    </section>
</x-store-layout>
