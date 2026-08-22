<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_budgets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_budgets');
    }
};
