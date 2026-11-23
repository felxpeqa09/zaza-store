@props(['products'])
@if ($products->hasPages())
    <div class="zaza-pagination mt-4">
        {{ $products->links('pagination::bootstrap-5') }}
    </div>
@endif
