<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->string('status')->default('active'); // active, completed, abandoned
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
