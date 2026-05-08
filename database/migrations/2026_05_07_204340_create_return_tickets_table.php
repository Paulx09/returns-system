<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_tickets', function (Blueprint $table) {
            $table->uuid('ticket_id')->primary();
            $table->string('tracking_code', 50)->unique();
            $table->uuid('order_id');
            $table->enum('current_status', [
                'received',
                'under_review',
                'approved',
                'rejected',
                'more_information_requested',
                'closed'
            ])->default('received')->index(); // Index from the raw sql script
            $table->text('customer_comment')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps(); // includes created_at which needs an index
            $table->softDeletes();

            $table->index('created_at'); // Index from the raw sql script

            $table->foreign('order_id')->references('order_id')->on('external_orders_cache');
            $table->foreign('created_by_user_id')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_tickets');
    }
};
