<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_infos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stripe_payment_id');
            $table->string('amount');
            $table->timestamps();
        });

        Schema::table('orders',function (Blueprint $table) {
            $table->foreignUuid('payment_info_id')->constrained('payment_infos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_infos');
    }
};
