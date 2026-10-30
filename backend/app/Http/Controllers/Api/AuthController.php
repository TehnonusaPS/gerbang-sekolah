<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Handle user login.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        // 1. Validate request parameters
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Normalize and retrieve user with role relationship
        $email = strtolower(trim($request->email));
        $user = User::with(['role', 'foundation', 'school.foundation', 'school.curriculum'])->where('email', $email)->first();

        // 3. Verify credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.'
            ], 401);
        }

        // 4. Verify account status
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Akun Anda dinonaktifkan.'
            ], 403);
        }

        // 5. Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Map response to match frontend expectations
        $mappedUser = [
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => $user->role ? $user->role->name : null,
            'roleLabel'     => $user->role ? $user->role->label : null,
            'phone'         => $user->phone,
            'photo'         => $user->photo,
            'is_active'     => $user->is_active,
            'foundation_id' => $user->foundation_id,
            'school_id'     => $user->school_id,
            'foundation'    => $user->foundation,
            'school'        => $user->school,
        ];

        return response()->json([
            'message'      => 'Login berhasil.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $mappedUser
        ]);
    }

    /**
     * Get authenticated user profile.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'role',
            'foundation',
            'school.foundation',
            'school.curriculum',
            'teacherProfile',
            'studentProfile.classroom',
            'parentProfile.students.user',
            'parentProfile.students.classroom'
        ]);

        $nameParts = explode(' ', $user->name ?? '');
        $namaDepan = $nameParts[0] ?? '';
        $namaBelakang = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

        $mappedUser = [
            'id'              => $user->id,
            'name'            => $user->name,
            'nama_depan'      => $namaDepan,
            'nama_belakang'   => $namaBelakang,
            'email'           => $user->email,
            'emailLogin'      => $user->email,
            'role'            => $user->role ? $user->role->name : null,
            'roleLabel'       => $user->role ? $user->role->label : null,
            'phone'           => $user->phone,
            'noHpLogin'       => $user->phone,
            'photo'           => $user->photo,
            'is_active'       => $user->is_active,
            'foundation_id'   => $user->foundation_id,
            'school_id'       => $user->school_id,
            'foundation'      => $user->foundation,
            'school'          => $user->school,
            'teacher_profile' => $user->teacherProfile,
            'student_profile' => $user->studentProfile,
            'parent_profile'  => $user->parentProfile,
        ];

        return response()->json([
            'user' => $mappedUser
        ]);
    }

    /**
     * Update authenticated user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'teacherProfile', 'studentProfile', 'parentProfile']);

        $request->validate([
            'nama_depan'     => 'nullable|string|max:100',
            'nama_belakang'  => 'nullable|string|max:100',
            'name'           => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:30',
            'noHpLogin'      => 'nullable|string|max:30',
            'photo'          => 'nullable|image|max:2048',
            'foto'           => 'nullable|image|max:2048',
            'password_lama'  => 'nullable|string',
            'password_baru'  => 'nullable|string|min:8|confirmed',
        ], [
            'password_baru.min'       => 'Password baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        // Cek ganti password jika diisi
        if ($request->filled('password_baru')) {
            if (!$request->filled('password_lama')) {
                return response()->json([
                    'message' => 'Password lama wajib diisi untuk mengubah password.',
                    'errors'  => ['password_lama' => ['Password lama wajib diisi.']]
                ], 422);
            }

            if (!Hash::check($request->password_lama, $user->password)) {
                return response()->json([
                    'message' => 'Password lama yang Anda masukkan tidak sesuai.',
                    'errors'  => ['password_lama' => ['Password lama yang Anda masukkan tidak sesuai.']]
                ], 422);
            }

            $user->password = Hash::make($request->password_baru);
        }

        // Tentukan nama lengkap
        if ($request->filled('nama_depan')) {
            $user->name = trim($request->input('nama_depan') . ' ' . ($request->input('nama_belakang') ?: ''));
        } elseif ($request->filled('name')) {
            $user->name = trim($request->input('name'));
        }

        // Telepon
        if ($request->has('noHpLogin')) {
            $user->phone = $request->input('noHpLogin');
        } elseif ($request->has('phone')) {
            $user->phone = $request->input('phone');
        }

        // Foto Profil
        $photoFile = $request->file('foto') ?: $request->file('photo');
        if ($photoFile) {
            $path = $photoFile->store('photos', 'public');
            $user->photo = $path;
        }

        $user->save();

        // Normalisasi gender
        $rawGender = $request->input('jenis_kelamin') ?: $request->input('kelamin_wali') ?: $request->input('gender');
        $gender = null;
        if (!empty($rawGender)) {
            $gender = in_array(strtolower($rawGender), ['male', 'jk01', 'laki-laki']) ? 'male' : 'female';
        }

        // Update profil spesifik role
        $roleName = $user->role ? $user->role->name : '';
        $staffRoles = [
            'guru',
            'wali_kelas',
            'kepala_sekolah',
            'tata_usaha',
            'admin_sekolah',
            'admin_yayasan',
            'superadmin'
        ];

        if (in_array($roleName, $staffRoles)) {
            \App\Models\TeacherProfile::updateOrCreate(
                ['user_id' => $user->id],
                array_filter([
                    'nik'               => $request->input('nik'),
                    'nip_nuptk'         => $request->input('nip_nuptk') ?: $request->input('nip'),
                    'front_title'       => $request->input('gelar_depan') ?: $request->input('front_title'),
                    'back_title'        => $request->input('gelar_belakang') ?: $request->input('back_title'),
                    'birth_place'       => $request->input('tempat_lahir') ?: $request->input('birth_place'),
                    'birth_date'        => $request->input('tanggal_lahir') ?: $request->input('birth_date'),
                    'gender'            => $gender,
                    'religion'          => $request->input('agama') ?: $request->input('religion'),
                    'marital_status'    => $request->input('status_pernikahan') ?: $request->input('marital_status'),
                    'last_education'    => $request->input('pendidikan_terakhir') ?: $request->input('last_education'),
                    'address'           => $request->input('alamat') ?: $request->input('address'),
                ], fn($val) => $val !== null)
            );
        } elseif ($roleName === 'siswa') {
            \App\Models\StudentProfile::updateOrCreate(
                ['user_id' => $user->id],
                array_filter([
                    'nik'         => $request->input('nik'),
                    'birth_place' => $request->input('tempat_lahir') ?: $request->input('birth_place'),
                    'birth_date'  => $request->input('tanggal_lahir') ?: $request->input('birth_date'),
                    'gender'      => $gender,
                    'address'     => $request->input('alamat') ?: $request->input('address'),
                ], fn($val) => $val !== null)
            );
        } elseif ($roleName === 'orang_tua') {
            \App\Models\ParentProfile::updateOrCreate(
                ['user_id' => $user->id],
                array_filter([
                    'nik'          => $request->input('nik'),
                    'gender'       => $gender,
                    'occupation'   => $request->input('pekerjaan_wali') ?: $request->input('occupation'),
                    'relationship' => $request->input('hubungan_siswa') ?: $request->input('relationship'),
                    'address'      => $request->input('alamat') ?: $request->input('address'),
                ], fn($val) => $val !== null)
            );
        }

        return $this->user($request);
    }

    /**
     * Update authenticated user password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'password_lama' => 'required|string',
            'password_baru' => 'required|string|min:8|confirmed',
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.min'      => 'Password baru minimal 8 karakter.',
            'password_baru.confirmed'=> 'Konfirmasi password baru tidak cocok.',
        ]);

        if (!Hash::check($request->password_lama, $user->password)) {
            return response()->json([
                'message' => 'Password lama yang Anda masukkan tidak sesuai.'
            ], 422);
        }

        $user->password = Hash::make($request->password_baru);
        $user->save();

        return response()->json([
            'message' => 'Password berhasil diperbarui.'
        ]);
    }

    /**
     * Handle user logout.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current user access token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout.'
        ]);
    }
}
