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
        Schema::create('shipping_infos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('other')->nullable();
            $table->string('pincode');
            $table->timestamps();
        });

        Schema::table('orders',function (Blueprint $table) {
            $table->foreignUuid('shipping_info_id')->constrained('shipping_infos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_infos');
    }
};
