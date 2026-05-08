<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_status_history', function (Blueprint $table) {
            $table->uuid('history_id')->primary();
            $table->uuid('ticket_id')->index(); // Index from raw sql
            $table->enum('old_status', [
                'received',
                'under_review',
                'approved',
                'rejected',
                'more_information_requested',
                'closed'
            ])->nullable();
            $table->enum('new_status', [
                'received',
                'under_review',
                'approved',
                'rejected',
                'more_information_requested',
                'closed'
            ]);
            $table->uuid('changed_by_user_id')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->foreign('ticket_id')->references('ticket_id')->on('return_tickets');
            $table->foreign('changed_by_user_id')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_status_history');
    }
};
