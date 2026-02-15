<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_request_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_request_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('value');
            $table->timestamps();

            $table->unique(['search_request_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_request_attributes');
    }
};
