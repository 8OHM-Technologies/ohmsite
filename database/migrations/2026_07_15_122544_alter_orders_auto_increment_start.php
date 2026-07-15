<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER SEQUENCE orders_id_seq RESTART WITH 10000;');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE orders AUTO_INCREMENT = 10000;');
        } elseif ($driver === 'sqlite') {
            DB::statement("INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES ('orders', 9999);");
            DB::statement("UPDATE sqlite_sequence SET seq = 9999 WHERE name = 'orders';");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER SEQUENCE orders_id_seq RESTART WITH 1;');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE orders AUTO_INCREMENT = 1;');
        } elseif ($driver === 'sqlite') {
            DB::statement("UPDATE sqlite_sequence SET seq = 0 WHERE name = 'orders';");
        }
    }
};
