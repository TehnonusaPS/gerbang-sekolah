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
        Schema::create('asets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('category', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('building', 100)->nullable();
            $table->string('floor', 10)->nullable();
            $table->string('room', 100)->nullable();
            $table->enum('condition', ['Baik', 'Rusak Ringan', 'Rusak Berat'])->default('Baik');
            $table->date('date')->nullable();
            $table->bigInteger('value')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asets');
    }
};
