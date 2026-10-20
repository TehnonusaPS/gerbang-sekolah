<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\ParentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminSekolah;
    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminSekolah = User::where('email', 'adminsekolah@mail.com')->first();
        $this->classroom = Classroom::first();
    }

    public function test_school_admin_can_create_student_with_mappings(): void
    {
        $payload = [
            'nama_depan'     => 'Ahmad',
            'nama_belakang'  => 'Pratama',
            'nisn'           => '9988776655',
            'kelas'          => $this->classroom->id,
            'status'         => '1', // Aktif -> active
            'jenis_kelamin'  => 'JK01', // Laki-laki -> male
            'tanggal_lahir'  => '2015-05-20',
            'email'          => 'ahmad.pratama@mail.com',
            'no_hp'          => '081234567890',
            'alamat'         => 'Jl. Testing No. 123',
            'tahun_masuk'    => '2026-07-01',
            'nama_wali'      => 'Bapak Ahmad',
            'hubungan_siswa' => 'ayah',
            'kelamin_wali'   => 'JK01',
            'pekerjaan_wali' => 'pns',
            'email_wali'     => 'ortu.ahmad@mail.com',
            'no_hp_wali'     => '081298765432',
        ];

        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->postJson('/api/management/students', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $studentId = $response->json('data.id');

        // Assert student was created in database correctly with mapped values
        $this->assertDatabaseHas('users', [
            'id' => $studentId,
            'name' => 'Ahmad Pratama',
            'email' => 'ahmad.pratama@mail.com',
        ]);

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $studentId,
            'classroom_id' => $this->classroom->id,
            'nisn' => '9988776655',
            'gender' => 'male',
            'status' => 'active',
            'address' => 'Jl. Testing No. 123',
        ]);

        // Assert parent profile was created and mapped correctly
        $this->assertDatabaseHas('parent_profiles', [
            'gender' => 'male',
            'relationship' => 'father',
            'occupation' => 'pns',
        ]);
    }

    public function test_school_admin_can_retrieve_student_details_with_correct_option_codes(): void
    {
        // 1. Create student
        $studentUser = User::create([
            'name' => 'Siti Aminah',
            'email' => 'siti.aminah@mail.com',
            'password' => bcrypt('123456'),
            'role_id' => Role::where('name', 'siswa')->first()->id,
            'school_id' => $this->adminSekolah->school_id,
        ]);

        $profile = StudentProfile::create([
            'user_id' => $studentUser->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '1122334455',
            'gender' => 'female',
            'status' => 'expelled', // Nonaktif -> '0'
            'birth_date' => '2016-04-12',
            'enrollment_date' => '2026-07-01',
        ]);

        // 2. Fetch details (show)
        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->getJson("/api/management/students/{$studentUser->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.jenis_kelamin', 'JK02') // female -> JK02
            ->assertJsonPath('data.status', '0') // expelled -> 0
            ->assertJsonPath('data.kelas', (string)$this->classroom->id)
            ->assertJsonPath('data.kelas_nama', $this->classroom->name);
    }

    public function test_school_admin_can_update_student_with_mappings(): void
    {
        // 1. Create student
        $studentUser = User::create([
            'name' => 'Fajar Shidiq',
            'email' => 'fajar.shidiq@mail.com',
            'password' => bcrypt('123456'),
            'role_id' => Role::where('name', 'siswa')->first()->id,
            'school_id' => $this->adminSekolah->school_id,
        ]);

        $profile = StudentProfile::create([
            'user_id' => $studentUser->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '7766554433',
            'gender' => 'male',
            'status' => 'active',
            'birth_date' => '2015-10-10',
            'enrollment_date' => '2026-07-01',
        ]);

        // 2. Update student
        $updatePayload = [
            'nama_depan'     => 'Fajar',
            'nama_belakang'  => 'Setiawan',
            'nisn'           => '7766554433',
            'kelas'          => $this->classroom->id,
            'status'         => '2', // Pindah -> transferred
            'jenis_kelamin'  => 'JK02', // Change gender to JK02 (female)
            'tanggal_lahir'  => '2015-10-10',
            'email'          => 'fajar.shidiq@mail.com',
        ];

        $response = $this->actingAs($this->adminSekolah, 'sanctum')
            ->putJson("/api/management/students/{$studentUser->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // 3. Verify in database
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $studentUser->id,
            'gender' => 'female',
            'status' => 'transferred',
        ]);
        
        $this->assertDatabaseHas('users', [
            'id' => $studentUser->id,
            'name' => 'Fajar Setiawan',
        ]);
    }
    public function test_school_admin_can_update_student_with_photo_multipart(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            "*/api/represent" => \Illuminate\Support\Facades\Http::response([
                "success" => true,
                "embedding" => array_fill(0, 512, 0.05),
            ], 200),
        ]);

        $studentUser = User::create([
            "name" => "Budi Santoso",
            "email" => "budi.santoso@mail.com",
            "password" => bcrypt("123456"),
            "role_id" => Role::where("name", "siswa")->first()->id,
            "school_id" => $this->adminSekolah->school_id,
        ]);

        $profile = StudentProfile::create([
            "user_id" => $studentUser->id,
            "classroom_id" => $this->classroom->id,
            "nisn" => "5544332211",
            "gender" => "male",
            "status" => "active",
            "birth_date" => "2015-08-15",
            "enrollment_date" => "2026-07-01",
        ]);

        $fakePhoto = \Illuminate\Http\UploadedFile::fake()->image("budi.jpg", 200, 200);

        $payload = [
            "_method"        => "PUT",
            "nama_depan"     => "Budi",
            "nama_belakang"  => "Santoso",
            "nisn"           => "5544332211",
            "kelas"          => $this->classroom->id,
            "status"         => "1",
            "jenis_kelamin"  => "JK01",
            "tanggal_lahir"  => "2015-08-15",
            "email"          => "budi.santoso@mail.com",
            "foto"           => $fakePhoto,
        ];

        $response = $this->actingAs($this->adminSekolah, "sanctum")
            ->post("/api/management/students/{$studentUser->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath("status", "success");

        $studentUser->refresh();
        $this->assertNotNull($studentUser->photo);
        $profile->refresh();
        $this->assertTrue($profile->is_face_registered);
    }
}
