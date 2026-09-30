<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();                                 // bigint unsigned — fine for the PK

            // ✅ Match users.id / products.id / product_variants.id
            //    They are INT UNSIGNED (32-bit), not BIGINT.
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variant_id')->default(0);
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'product_id', 'variant_id']);
            $table->index('user_id');

            // Safe to add FKs now that types match
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            // variant_id has a default of 0 which is not a valid product_variants.id, so
            // we intentionally skip the FK for it.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};