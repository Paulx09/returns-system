<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_orders_cache', function (Blueprint $table) {
            $table->uuid('order_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('order_number', 50)->unique();
            $table->string('customer_full_name', 150);
            $table->string('customer_email', 150);
            $table->string('customer_dni', 15)->index(); // Added index from the raw sql script
            $table->timestamp('order_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_orders_cache');
    }
};
