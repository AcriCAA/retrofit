<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature');
            $table->string('model');
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
