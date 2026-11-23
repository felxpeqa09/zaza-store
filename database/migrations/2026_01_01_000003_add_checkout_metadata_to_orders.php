<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->uuid('checkout_token')->nullable();
            $table->string('checkout_url', 2048)->nullable();
            $table->string('approved_payment_id')->nullable()->unique();
            $table->unique(['user_id', 'checkout_token']);
        });
        DB::statement("CREATE UNIQUE INDEX orders_one_pending_checkout ON orders (user_id) WHERE estado = 'PENDIENTE' AND checkout_token IS NOT NULL");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX orders_one_pending_checkout');
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'checkout_token']);
            $table->dropUnique(['approved_payment_id']);
            $table->dropColumn(['checkout_token', 'checkout_url', 'approved_payment_id']);
        });
    }
};
