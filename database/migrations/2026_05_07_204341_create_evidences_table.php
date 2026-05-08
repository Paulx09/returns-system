<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidences', function (Blueprint $table) {
            $table->uuid('evidence_id')->primary();
            $table->uuid('ticket_id');
            $table->string('file_name', 255);
            $table->text('file_path');
            $table->string('mime_type', 100);
            $table->timestamp('uploaded_at')->useCurrent();
            $table->softDeletes();

            $table->foreign('ticket_id')->references('ticket_id')->on('return_tickets');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidences');
    }
};
