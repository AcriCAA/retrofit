<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default('active'); // active, paused, completed, expired
            $table->integer('search_frequency_minutes')->default(60);
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_requests');
    }
};
