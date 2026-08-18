<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sponsors', function (Blueprint $table) {
            $table->unsignedInteger('max_guest_accounts')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('event_sponsors', function (Blueprint $table) {
            $table->dropColumn('max_guest_accounts');
        });
    }
};
