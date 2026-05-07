<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_items', function (Blueprint $table) {
            $table->uuid('return_item_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('ticket_id')->index(); // Index from raw sql
            $table->uuid('order_item_id');
            $table->uuid('reason_id');
            $table->integer('quantity_to_return')->unsigned(); // Must be > 0
            $table->text('admin_comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ticket_id')->references('ticket_id')->on('return_tickets');
            $table->foreign('order_item_id')->references('order_item_id')->on('order_items');
            $table->foreign('reason_id')->references('reason_id')->on('return_reasons');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
