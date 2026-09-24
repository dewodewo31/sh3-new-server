<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('inventory_categories')->restrictOnDelete();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->enum('status', ['available', 'borrowed', 'in_maintenance', 'lost', 'retired'])->default('available');
            $table->enum('condition', ['excellent', 'good', 'fair', 'damaged', 'critical'])->default('good');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventory_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->enum('context', ['gallery', 'before_loan', 'during_loan', 'return', 'damage', 'maintenance', 'other'])->default('gallery');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('caption')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_photos');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_categories');
    }
};
