<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'admin_full_access', 'admin_laman', 'admin_member',
            'admin_bnh', 'organizer', 'bendahara', 'sponsor', 'merchandise',
            'participant'
        ) DEFAULT 'participant'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'admin_full_access', 'admin_laman', 'admin_member',
            'admin_bnh', 'organizer', 'bendahara', 'sponsor', 'merchandise'
        ) DEFAULT 'admin_full_access'");
    }
};
