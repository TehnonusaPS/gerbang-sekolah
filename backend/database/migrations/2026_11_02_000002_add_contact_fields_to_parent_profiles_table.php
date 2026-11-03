<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->change();
            $table->string('name')->nullable()->after('user_id');
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
        });

        // Allow custom guardian relationship values (Kakek, Nenek, Paman, Bibi, Kakak, Saudara lainnya, Orang lain)
        DB::statement('ALTER TABLE parent_profiles DROP CONSTRAINT IF EXISTS parent_profiles_relationship_check');
        DB::statement('ALTER TABLE parent_profiles ALTER COLUMN relationship TYPE VARCHAR(100)');

        // Make users.email nullable so students can log in with NISN without requiring an email
        DB::statement('ALTER TABLE users ALTER COLUMN email DROP NOT NULL');

        // Backfill name, email, phone from linked users for existing parent_profiles
        DB::statement("
            UPDATE parent_profiles pp
            SET name = COALESCE(pp.name, u.name),
                email = COALESCE(pp.email, u.email),
                phone = COALESCE(pp.phone, u.phone)
            FROM users u
            WHERE pp.user_id = u.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->dropColumn(['name', 'email', 'phone']);
        });
    }
};

