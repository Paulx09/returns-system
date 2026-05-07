<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = File::get(base_path('DATABASE.md'));
        DB::unprepared($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('
            DROP TABLE IF EXISTS ticket_status_history CASCADE;
            DROP TABLE IF EXISTS evidences CASCADE;
            DROP TABLE IF EXISTS return_items CASCADE;
            DROP TABLE IF EXISTS return_tickets CASCADE;
            DROP TABLE IF EXISTS order_items CASCADE;
            DROP TABLE IF EXISTS external_orders_cache CASCADE;
            DROP TABLE IF EXISTS return_reasons CASCADE;
            DROP TABLE IF EXISTS users CASCADE;
            DROP TYPE IF EXISTS ticket_status CASCADE;
            DROP TYPE IF EXISTS user_role CASCADE;
        ');
    }
};
