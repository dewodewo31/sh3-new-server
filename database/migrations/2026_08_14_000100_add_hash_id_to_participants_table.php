<?php

use App\Models\Participant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('hash_id')->nullable()->unique()->after('user_id');
        });

        // Backfill existing participants with a unique participant code.
        DB::table('participants')
            ->whereNull('hash_id')
            ->orWhere('hash_id', '')
            ->orderBy('id')
            ->get()
            ->each(function ($row) {
                DB::table('participants')
                    ->where('id', $row->id)
                    ->update(['hash_id' => Participant::generateHashId()]);
            });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('hash_id');
        });
    }
};
