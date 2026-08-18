<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('participants', 'participant_code') && ! Schema::hasColumn('participants', 'hash_id')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->renameColumn('participant_code', 'hash_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('participants', 'hash_id') && ! Schema::hasColumn('participants', 'participant_code')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->renameColumn('hash_id', 'participant_code');
            });
        }
    }
};
