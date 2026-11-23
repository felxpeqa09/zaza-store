<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['order_items', 'cart_items'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->index('product_variant_id'));
        }
    }

    public function down(): void
    {
        foreach (['order_items', 'cart_items'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropIndex(['product_variant_id']));
        }
    }
};
