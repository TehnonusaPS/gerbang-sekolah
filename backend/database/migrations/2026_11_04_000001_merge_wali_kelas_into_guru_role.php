<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $guruRole = Role::where('name', 'guru')->first();
        $waliRole = Role::where('name', 'wali_kelas')->first();

        if ($guruRole && $waliRole) {
            // Update all users who have role wali_kelas to guru
            User::where('role_id', $waliRole->id)->update(['role_id' => $guruRole->id]);

            // Delete wali_kelas from roles table
            $waliRole->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::firstOrCreate(
            ['name' => 'wali_kelas'],
            [
                'label'       => 'WALI KELAS',
                'description' => 'Homeroom teacher with additional access to manage their class.',
            ]
        );
    }
};
