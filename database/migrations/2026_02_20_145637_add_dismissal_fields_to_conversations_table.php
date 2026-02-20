<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('type')->default('item_identification')->after('status');
            $table->foreignId('search_request_id')->nullable()->constrained()->nullOnDelete()->after('type');
            $table->foreignId('search_result_id')->nullable()->constrained('search_results')->nullOnDelete()->after('search_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['search_request_id']);
            $table->dropForeign(['search_result_id']);
            $table->dropColumn(['type', 'search_request_id', 'search_result_id']);
        });
    }
};
