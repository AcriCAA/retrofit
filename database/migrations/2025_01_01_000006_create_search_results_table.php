<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_request_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace');
            $table->string('external_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('condition')->nullable();
            $table->string('seller_name')->nullable();
            $table->string('url');
            $table->string('image_url')->nullable();
            $table->decimal('relevance_score', 5, 4)->default(0);
            $table->string('user_status')->default('new'); // new, viewed, saved, dismissed
            $table->boolean('is_notified')->default(false);
            $table->timestamps();

            $table->unique(['search_request_id', 'marketplace', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_results');
    }
};
