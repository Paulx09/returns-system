<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // En Postgres puro, se crearía el ENUM nativo.
        // DB::statement("CREATE TYPE user_role AS ENUM ('admin', 'support')");
        
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('full_name', 150);
            $table->string('email', 150)->unique();
            $table->text('password_hash');
            $table->enum('role', ['admin', 'support']);
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        // DB::statement("DROP TYPE IF EXISTS user_role");
    }
};
