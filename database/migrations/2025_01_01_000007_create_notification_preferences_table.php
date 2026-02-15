<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('email_enabled')->default(true);
            $table->string('email_frequency')->default('instant'); // instant, daily, weekly
            $table->boolean('notify_new_results')->default(true);
            $table->boolean('notify_price_drops')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
