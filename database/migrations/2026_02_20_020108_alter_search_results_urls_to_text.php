<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_results', function (Blueprint $table) {
            $table->text('url')->change();
            $table->text('image_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('search_results', function (Blueprint $table) {
            $table->string('url')->change();
            $table->string('image_url')->nullable()->change();
        });
    }
};
