<?php

namespace Tests\Feature;

use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\StudentProfile;
use App\Models\ParentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminYayasan;
    protected User $adminSekolah;
    protected User $siswaUser;

    protected Foundation $foundation;
    protected School $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed database (Roles, initial foundation, school, users)
        $this->seed();

        // Retrieve seeded roles
        $superadminRole = Role::where('name', 'superadmin')->first();
        $adminYayasanRole = Role::where('name', 'admin_yayasan')->first();
        $adminSekolahRole = Role::where('name', 'admin_sekolah')->first();
        $siswaRole = Role::where('name', 'siswa')->first();

        // Retrieve seeded foundation and school
        $this->foundation = Foundation::first();
        $this->school = School::first();

        // Retrieve / setup users
        $this->superAdmin = User::where('role_id', $superadminRole->id)->first();
        $this->adminYayasan = User::where('role_id', $adminYayasanRole->id)->first();
        
        // Retrieve seeded admin_sekolah user
        $this->adminSekolah = User::where('email', 'adminsekolah@mail.com')->first();

        // Retrieve student user
        $this->siswaUser = User::where('role_id', $siswaRole->id)->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Yayasan (Foundation) Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_crud_foundation(): void
    {
        // 1. Index
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/management/foundations');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['data']]);

        // 2. Store
        $newFoundationData = [
            'name'             => 'Yayasan Baru',
            'code'             => 'Y9999',
            'established_date' => '2026-01-01',
            'status'           => 'trial',
            'emailLogin'       => 'admin.baru.emailonly@example.com',
            'logo'             => \Illuminate\Http\UploadedFile::fake()->image('logo.png'),
        ];
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/management/foundations', $newFoundationData);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Yayasan Baru');

        $this->assertDatabaseHas('users', [
            'email' => 'admin.baru.emailonly@example.com',
            'phone' => null,
        ]);

        $foundationId = $response->json('data.id');

        // 3. Show
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/management/foundations/{$foundationId}");
        $response->assertStatus(200)
            ->assertJsonPath('data.code', 'Y9999');

        // 4. Update
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson("/api/management/foundations/{$foundationId}", [
                'name'   => 'Yayasan Baru Diubah',
                'code'   => 'Y9999-REV',
                'status' => 'active',
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Yayasan Baru Diubah')
            ->assertJsonPath('data.code', 'Y9999-REV')
            ->assertJsonPath('data.status', 'active');

        // 5. Destroy
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/management/foundations/{$foundationId}");
        $response->assertStatus(200);

        $this->assertSoftDeleted('foundations', ['id' => $foundationId]);
    }

    public function test_foundation_duplicate_admin_phone_is_rejected_on_create_and_update(): void
    {
        // 1. Duplicate phone rejected on create
        $this->adminYayasan->update(['phone' => '081234567890']);
        $existingPhone = '081234567890';

        $resStore = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/management/foundations', [
                'name'       => 'Yayasan Gagal Duplikat HP',
                'emailLogin' => 'admin.uniqfail@yayasan.org',
                'noHpLogin'  => $existingPhone,
                'logo'       => \Illuminate\Http\UploadedFile::fake()->image('logo.png'),
            ]);

        $resStore->assertStatus(422)
            ->assertJsonValidationErrors(['noHpLogin']);
        $this->assertEquals(
            'No. HP login administrator sudah digunakan.',
            $resStore->json('errors.noHpLogin.0')
        );

        // 2. Duplicate phone rejected on update
        $resCreate = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/management/foundations', [
                'name'       => 'Yayasan Change Phone Test',
                'emailLogin' => 'admin.changephone@yayasan.org',
                'noHpLogin'  => '081299112233',
                'logo'       => \Illuminate\Http\UploadedFile::fake()->image('logo.png'),
            ]);
        $resCreate->assertStatus(201);
        $foundationId = $resCreate->json('data.id');

        $resUpdate = $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson("/api/management/foundations/{$foundationId}", [
                'name'      => 'Yayasan Should Not Change Phone',
                'noHpLogin' => $existingPhone,
            ]);

        $resUpdate->assertStatus(422)
            ->assertJsonValidationErrors(['noHpLogin']);
        $this->assertEquals(
            'No. HP login administrator sudah digunakan.',
            $resUpdate->json('errors.noHpLogin.0')
        );
    }

    public function test_store_foundation_requires_logo(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/management/foundations', [
                'name'       => 'Yayasan Tanpa Logo',
                'emailLogin' => 'admin.nologo@yayasan.org',
                'noHpLogin'  => '081233440001',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
        $this->assertEquals(
            'Logo yayasan wajib diunggah.',
            $response->json('errors.logo.0')
        );
    }

    public function test_store_foundation_rejects_invalid_logo_format_and_size(): void
    {
        // 1. Non-image format (PDF)
        $fakePdf = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        $resPdf = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/management/foundations', [
                'name'       => 'Yayasan Format Logo PDF',
                'emailLogin' => 'admin.pdflogo@yayasan.org',
                'noHpLogin'  => '081200003311',
                'logo'       => $fakePdf,
            ]);

        $resPdf->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
        $this->assertEquals(
            'Logo yayasan harus berupa berkas gambar JPG, JPEG, PNG, atau WEBP.',
            $resPdf->json('errors.logo.0')
        );

        // 2. Oversized image (> 2MB)
        $fakeLarge = \Illuminate\Http\UploadedFile::fake()->image('too_large.jpg')->size(3000);
        $resLarge = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/management/foundations', [
                'name'       => 'Yayasan Logo Terlalu Besar',
                'emailLogin' => 'admin.largelogo@yayasan.org',
                'noHpLogin'  => '081200003322',
                'logo'       => $fakeLarge,
            ]);

        $resLarge->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
        $this->assertEquals(
            'Ukuran logo yayasan tidak boleh lebih dari 2 MB.',
            $resLarge->json('errors.logo.0')
        );
    }

    public function test_superadmin_can_search_and_filter_foundations(): void
    {
        // Create auxiliary foundations
        Foundation::create(['name' => 'Yayasan Alfa', 'code' => 'YALFA', 'status' => 'active']);
        Foundation::create(['name' => 'Yayasan Beta', 'code' => 'YBETA', 'status' => 'trial']);
        Foundation::create(['name' => 'Yayasan Gamma', 'code' => 'YGAMMA', 'status' => 'inactive']);

        // 1. Search by name
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/management/foundations?search=Alfa');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('Yayasan Alfa', $data[0]['name']);

        // 2. Search by code
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/management/foundations?search=YBETA');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('Yayasan Beta', $data[0]['name']);

        // 3. Filter by status
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/management/foundations?status=trial');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        foreach ($data as $item) {
            $this->assertEquals('trial', $item['status']);
        }

        // 4. Pagination per_page
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/management/foundations?per_page=2');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data'));
        $this->assertEquals(2, $response->json('data.per_page'));
    }

    public function test_admin_yayasan_scoping_for_foundation(): void
    {
        // 1. Index should return only their own foundation
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->getJson('/api/management/foundations');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals($this->foundation->id, $response->json('data.data.0.id'));

        // 2. Store should be forbidden
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->postJson('/api/management/foundations', [
                'name' => 'Yayasan Ilegal',
                'code' => 'YILLEGAL',
            ]);
        $response->assertStatus(403);

        // 3. Show own foundation is allowed
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->getJson("/api/management/foundations/{$this->foundation->id}");
        $response->assertStatus(200);

        // Show other foundation is forbidden
        $otherFoundation = Foundation::create(['name' => 'Other', 'code' => 'YOTHER']);
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->getJson("/api/management/foundations/{$otherFoundation->id}");
        $response->assertStatus(403);

        // 4. Update own foundation is allowed but strips code and status
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->putJson("/api/management/foundations/{$this->foundation->id}", [
                'name'   => 'Yayasan Nusantara Pintar Updated',
                'code'   => 'Y_ATTEMPT_CHANGE',
                'status' => 'inactive',
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Yayasan Nusantara Pintar Updated')
            ->assertJsonPath('data.code', $this->foundation->code) // code remains unchanged
            ->assertJsonPath('data.status', 'active'); // status remains active

        // 5. Destroy is forbidden
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->deleteJson("/api/management/foundations/{$this->foundation->id}");
        $response->assertStatus(403);
    }

    public function test_admin_sekolah_cannot_access_foundation(): void
    {
        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->getJson('/api/management/foundations');
        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Sekolah (School) Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_crud_schools(): void
    {
        // Store
        $newSchoolData = [
            'foundation_id' => $this->foundation->id,
            'name'          => 'SMP Nusantara Pintar',
            'npsn'          => '20100999',
            'level'         => 'SMP',
            'status'        => 'active',
        ];
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/management/schools', $newSchoolData);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'SMP Nusantara Pintar');

        $schoolId = $response->json('data.id');

        // Index
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/management/schools');
        $response->assertStatus(200);

        // Update
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson("/api/management/schools/{$schoolId}", [
                'name' => 'SMP Nusantara Pintar Updated',
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'SMP Nusantara Pintar Updated');

        // Delete
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/management/schools/{$schoolId}");
        $response->assertStatus(200);

        $this->assertSoftDeleted('schools', ['id' => $schoolId]);
    }

    public function test_admin_yayasan_scoping_for_schools(): void
    {
        // Store under own foundation
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->postJson('/api/management/schools', [
                'name' => 'SMP Yayasan Kami',
                'npsn' => '88888888',
                'level'=> 'SMP',
            ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.foundation_id', $this->foundation->id);

        $schoolId = $response->json('data.id');

        // Try to store school pointing to another foundation - should enforce/match own foundation or fail validation
        $otherFoundation = Foundation::create(['name' => 'Other Foundation', 'code' => 'YOTH']);
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->postJson('/api/management/schools', [
                'foundation_id' => $otherFoundation->id,
                'name'          => 'SMP Yayasan Lain',
                'npsn'          => '99999999',
            ]);
        // Validation fails because foundation_id must be in current foundation_id list
        $response->assertStatus(422);

        // Show school
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->getJson("/api/management/schools/{$schoolId}");
        $response->assertStatus(200);

        // Show school of another foundation
        $otherSchool = School::create([
            'foundation_id' => $otherFoundation->id,
            'name'          => 'Sekolah Lain',
            'status'        => 'active',
        ]);
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->getJson("/api/management/schools/{$otherSchool->id}");
        $response->assertStatus(403);

        // Update
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->putJson("/api/management/schools/{$schoolId}", [
                'name'          => 'SMP Yayasan Kami Updated',
                'foundation_id' => $otherFoundation->id, // Attempt to transfer
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'SMP Yayasan Kami Updated')
            ->assertJsonPath('data.foundation_id', $this->foundation->id); // Transfers are stripped/ignored

        // Delete
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->deleteJson("/api/management/schools/{$schoolId}");
        $response->assertStatus(200);
    }

    public function test_admin_sekolah_scoping_for_schools(): void
    {
        // Index returns only their own school
        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->getJson('/api/management/schools');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals($this->school->id, $response->json('data.data.0.id'));

        // Store is forbidden
        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->postJson('/api/management/schools', [
                'name' => 'Sekolah Ilegal',
            ]);
        $response->assertStatus(403);

        // Update own school is allowed but strips restricted fields
        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->putJson("/api/management/schools/{$this->school->id}", [
                'name'          => 'SD Nusantara Pintar Bekasi Updated',
                'status'        => 'inactive', // Attempt to block school
                'foundation_id' => 9999, // Attempt to switch foundation
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'SD Nusantara Pintar Bekasi Updated')
            ->assertJsonPath('data.status', 'active') // remains active
            ->assertJsonPath('data.foundation_id', $this->foundation->id); // remains foundation

        // Delete is forbidden
        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->deleteJson("/api/management/schools/{$this->school->id}");
        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Pengguna (User) & Profile Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_crud_users_with_profiles(): void
    {
        $guruRole = Role::where('name', 'guru')->first();

        // 1. Store user with teacher profile
        $userData = [
            'name'          => 'Guru Test Baru',
            'email'         => 'gurubaru@mail.com',
            'password'      => 'password123',
            'role_id'       => $guruRole->id,
            'foundation_id' => $this->foundation->id,
            'school_id'     => $this->school->id,
            'profile'       => [
                'nik'               => '1234567890123456',
                'nip_nuptk'         => 'NIP9999',
                'gender'            => 'male',
                'position'          => 'Guru Matematika',
                'employment_status' => 'honorer',
            ],
        ];

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/management/users', $userData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Guru Test Baru')
            ->assertJsonPath('data.teacher_profile.position', 'Guru Matematika');

        $userId = $response->json('data.id');

        $this->assertDatabaseHas('users', ['email' => 'gurubaru@mail.com']);
        $this->assertDatabaseHas('teacher_profiles', [
            'user_id'  => $userId,
            'nip_nuptk' => 'NIP9999',
        ]);

        // 2. Show user
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/management/users/{$userId}");
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['teacher_profile']]);

        // 3. Update user and change profile attributes
        $updateData = [
            'name'    => 'Guru Test Baru Updated',
            'email'   => 'gurubaru@mail.com',
            'role_id' => $guruRole->id,
            'profile' => [
                'position'          => 'Guru Fisika',
                'employment_status' => 'pns',
            ],
        ];

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson("/api/management/users/{$userId}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Guru Test Baru Updated')
            ->assertJsonPath('data.teacher_profile.position', 'Guru Fisika');

        $this->assertDatabaseHas('teacher_profiles', [
            'user_id'  => $userId,
            'position' => 'Guru Fisika',
        ]);

        // 4. Update user role category transition (Guru -> Siswa)
        $siswaRole = Role::where('name', 'siswa')->first();
        $transitionData = [
            'name'    => 'Siswa Transisi',
            'email'   => 'gurubaru@mail.com',
            'role_id' => $siswaRole->id,
            'profile' => [
                'nisn'   => '1000000001',
                'status' => 'active',
            ],
        ];

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson("/api/management/users/{$userId}", $transitionData);

        $response->assertStatus(200)
            ->assertJsonPath('data.role.name', 'siswa')
            ->assertJsonPath('data.student_profile.nisn', '1000000001');

        // Assert old teacher profile was deleted and new student profile created
        $this->assertDatabaseMissing('teacher_profiles', ['user_id' => $userId]);
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $userId,
            'nisn'    => '1000000001',
        ]);

        // 5. Delete user
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/management/users/{$userId}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('student_profiles', ['user_id' => $userId]);
    }

    public function test_admin_yayasan_scoping_for_users(): void
    {
        $guruRole = Role::where('name', 'guru')->first();
        $superadminRole = Role::where('name', 'superadmin')->first();

        // Store user under their foundation
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->postJson('/api/management/users', [
                'name'     => 'Guru Yayasan',
                'email'    => 'guruyayasan@mail.com',
                'password' => 'password123',
                'role_id'  => $guruRole->id,
                'school_id'=> $this->school->id,
            ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.foundation_id', $this->foundation->id);

        $userId = $response->json('data.id');

        // Cannot assign superadmin role
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->postJson('/api/management/users', [
                'name'     => 'Superadmin Baru',
                'email'    => 'superbaru@mail.com',
                'password' => 'password123',
                'role_id'  => $superadminRole->id,
            ]);
        $response->assertStatus(403);

        // Cannot update users of other foundations
        $otherFoundation = Foundation::create(['name' => 'Other Foundation', 'code' => 'YOTH3']);
        $otherUser = User::create([
            'name'          => 'Other User',
            'email'         => 'otheruser@mail.com',
            'password'      => bcrypt('password'),
            'role_id'       => $guruRole->id,
            'foundation_id' => $otherFoundation->id,
        ]);

        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->putJson("/api/management/users/{$otherUser->id}", [
                'name'    => 'Diubah',
                'email'   => 'otheruser@mail.com',
                'role_id' => $guruRole->id,
            ]);
        $response->assertStatus(403);

        // Can update user under their own foundation
        $response = $this->actingAs($this->adminYayasan, 'sanctum')
            ->putJson("/api/management/users/{$userId}", [
                'name'    => 'Guru Yayasan Updated',
                'email'   => 'guruyayasan@mail.com',
                'role_id' => $guruRole->id,
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Guru Yayasan Updated');
    }

    public function test_admin_sekolah_scoping_for_users(): void
    {
        $guruRole = Role::where('name', 'guru')->first();
        $adminYayasanRole = Role::where('name', 'admin_yayasan')->first();

        // Can list users in their school
        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->getJson('/api/management/users');
        $response->assertStatus(200);

        // Store user under school
        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->postJson('/api/management/users', [
                'name'     => 'Guru Sekolah Kami',
                'email'    => 'gurusekolah@mail.com',
                'password' => 'password123',
                'role_id'  => $guruRole->id,
            ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.school_id', $this->school->id)
            ->assertJsonPath('data.foundation_id', $this->foundation->id);

        $userId = $response->json('data.id');

        // Cannot assign Admin Yayasan role
        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->postJson('/api/management/users', [
                'name'     => 'Yayasan Admin Baru',
                'email'    => 'yayasanbaru@mail.com',
                'password' => 'password123',
                'role_id'  => $adminYayasanRole->id,
            ]);
        $response->assertStatus(403);

        // Cannot delete user from another school
        $otherSchool = School::create([
            'foundation_id' => $this->foundation->id,
            'name'          => 'Other School',
        ]);
        $otherUser = User::create([
            'name'          => 'Other School User',
            'email'         => 'otherschooluser@mail.com',
            'password'      => bcrypt('password'),
            'role_id'       => $guruRole->id,
            'school_id'     => $otherSchool->id,
            'foundation_id' => $this->foundation->id,
        ]);

        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->deleteJson("/api/management/users/{$otherUser->id}");
        $response->assertStatus(403);
    }
}
