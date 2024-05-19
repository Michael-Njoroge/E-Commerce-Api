<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 8, 2);
            $table->string('category');
            $table->string('brand');
            $table->integer('quantity');
            $table->integer('sold')->default(0);
            $table->decimal('total_ratings', 8, 2)->default(0);
            $table->json('color')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        Schema::create('user_products', function (Blueprint $table) {
             $table->uuid('id')->primary()->default(Str::uuid());
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('star')->nullable(false);
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(Str::uuid());
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->integer('count');
            $table->string('color');
            $table->decimal('price', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('user_products');
        Schema::dropIfExists('ratings');
    }
};
