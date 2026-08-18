<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookkeepings', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->text('description');
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->enum('category', ['sponsor', 'event_income', 'other'])->default('other');
            $table->foreignId('sponsor_id')->nullable()->constrained('sponsors')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('receipt')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookkeepings');
    }
};
