<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('order_item_id')->primary();
            $table->uuid('order_id');
            $table->string('product_code', 50);
            $table->string('product_name', 200);
            $table->integer('quantity')->unsigned(); // Must be > 0
            $table->decimal('unit_price', 10, 2)->unsigned(); // Must be >= 0
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('order_id')->on('external_orders_cache');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
