<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('subcategories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->string('nombre');
            $table->string('slug');
            $table->unique(['category_id', 'slug']);
            $table->unique(['category_id', 'id']);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion');
            $table->bigInteger('precio');
            $table->foreignId('category_id')->constrained()->index();
            $table->foreignId('subcategory_id')->nullable();
            $table->foreign(['category_id', 'subcategory_id'])->references(['category_id', 'id'])->on('subcategories');
            $table->boolean('activo')->default(false)->index();
            $table->boolean('destacado')->default(false)->index();
            $table->boolean('needs_price_review')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->string('ruta');
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('product_colors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('imagen')->nullable();
            $table->unique(['product_id', 'id']);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('product_color_id')->nullable();
            $table->foreign(['product_id', 'product_color_id'])->references(['product_id', 'id'])->on('product_colors');
            $table->string('talla', 20);
            $table->integer('stock')->default(0);
            $table->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'product_id']);
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained();
            $table->integer('cantidad');
            $table->unique(['cart_id', 'product_variant_id']);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->index();
            $table->string('referencia')->unique();
            $table->string('estado')->default('PENDIENTE');
            $table->string('medio_pago')->nullable();
            $table->string('forma_pago')->nullable();
            $table->timestampTz('fecha_pago')->nullable();
            $table->date('entrega_estimada')->nullable();
            $table->bigInteger('total');
            $table->jsonb('datos_envio');
            $table->timestampTz('stock_reserved_until')->nullable();
            $table->string('provider_preference_id')->nullable()->unique();
            $table->index(['estado', 'stock_reserved_until']);
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nombre');
            $table->string('color')->nullable();
            $table->string('talla', 20);
            $table->integer('cantidad');
            $table->bigInteger('precio_unitario');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->index();
            $table->string('proveedor');
            $table->string('provider_payment_id');
            $table->unique(['proveedor', 'provider_payment_id']);
            $table->string('estado');
            $table->bigInteger('importe');
            $table->char('moneda', 3);
            $table->jsonb('raw_payload');
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->string('imagen');
            $table->string('texto')->nullable();
            $table->string('enlace')->nullable();
            $table->boolean('activo')->default(false);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('keepalive_pings', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 20);
            $table->timestampTz('pinged_at')->index();
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_valid_values CHECK (precio >= 0)');

        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_valid_values CHECK (stock >= 0)');

        DB::statement('ALTER TABLE cart_items ADD CONSTRAINT cart_items_valid_values CHECK (cantidad > 0)');

        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_valid_values CHECK (total >= 0 AND estado IN ('PENDIENTE', 'PAGADO', 'EN_PREPARACION', 'ENVIADO', 'ENTREGADO', 'CANCELADO', 'EXPIRADO'))");

        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_valid_values CHECK (cantidad > 0 AND precio_unitario >= 0)');

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_valid_values CHECK (importe >= 0)');

        DB::statement('CREATE UNIQUE INDEX product_variants_with_color_unique ON product_variants (product_id, product_color_id, talla) WHERE product_color_id IS NOT NULL');

        DB::statement('CREATE UNIQUE INDEX product_variants_without_color_unique ON product_variants (product_id, talla) WHERE product_color_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('keepalive_pings');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_colors');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('subcategories');
        Schema::dropIfExists('categories');
    }
};
