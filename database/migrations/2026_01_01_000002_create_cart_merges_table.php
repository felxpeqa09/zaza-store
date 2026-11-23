<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_merges', function (Blueprint $table): void {
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->uuid('batch');
            $table->jsonb('notices');
            $table->primary(['cart_id', 'batch']);
        });

        DB::statement('ALTER TABLE public.cart_merges ENABLE ROW LEVEL SECURITY');
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_merges');
    }
};
