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
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->foreignUuid('advisor_id')->nullable()->after('description')->constrained('users')->nullOnDelete();
            $table->string('coach_name')->nullable()->after('advisor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->dropForeign(['advisor_id']);
            $table->dropColumn(['advisor_id', 'coach_name']);
        });
    }
};

