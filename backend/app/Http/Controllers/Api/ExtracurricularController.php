<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExtracurricularController extends Controller
{
    /**
     * Map frontend status (aktif / nonaktif) to backend is_active.
     */
    private function mapStatusToDb(?string $status): bool
    {
        if (!$status) return true;
        return in_array(strtolower($status), ['aktif', 'active', 'true', '1']);
    }

    private function mapDbToStatus(bool $isActive): string
    {
        return $isActive ? 'aktif' : 'nonaktif';
    }

    private function formatPembinaNama($advisor, ?string $advisorId, ?string $coachName): string
    {
        $parts = [];
        if ($advisor) {
            $parts[] = $advisor->name;
        } elseif ($advisorId) {
            $parts[] = 'Guru';
        }

        if (!empty($coachName)) {
            $parts[] = !empty($parts) ? "{$coachName} (Pelatih)" : $coachName;
        }

        return !empty($parts) ? implode(', ', $parts) : '-';
    }

    /**
     * Helper to get school scope for the current user.
     */
    private function getSchoolScope(Request $request, $user)
    {
        if ($user->isSuperAdmin()) {
            return $request->input('school_id');
        } elseif ($user->hasRole('admin_yayasan')) {
            $schoolId = $request->input('school_id');
            if ($schoolId) {
                $belongs = School::where('id', $schoolId)
                    ->where('foundation_id', $user->foundation_id)
                    ->exists();
                return $belongs ? $schoolId : -1;
            }
            return null;
        }
        return $user->school_id;
    }

    /**
     * Display a listing of extracurriculars.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Extracurricular::with(['advisor'])->withCount('students');

        if ($user->isSuperAdmin()) {
            if ($request->has('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            }
        } elseif ($user->hasRole('admin_yayasan')) {
            $query->whereHas('school', function ($q) use ($user) {
                $q->where('foundation_id', $user->foundation_id);
            });
            if ($request->has('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            }
        } else {
            $query->where('school_id', $user->school_id);
        }

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('coach_name', 'like', "%{$search}%")
                  ->orWhereHas('advisor', fn($aq) => $aq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $isActive = $this->mapStatusToDb($request->status);
            $query->where('is_active', $isActive);
        }

        $ekskuls = $query->latest()->get();

        $formatted = $ekskuls->map(function ($item) {
            return [
                'id'             => (string) $item->id,
                'nama'           => $item->name,
                'deskripsi'      => $item->description,
                'advisor_id'     => $item->advisor_id ? (string) $item->advisor_id : null,
                'pembina_nama'   => $this->formatPembinaNama($item->advisor, $item->advisor_id, $item->coach_name),
                'coach_name'     => $item->coach_name ?? '',
                'jumlah_anggota' => (int) ($item->students_count ?? 0),
                'status'         => $this->mapDbToStatus($item->is_active),
                'school_id'      => $item->school_id,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $formatted,
        ]);
    }

    /**
     * Store a newly created extracurricular.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->hasAnyRole(['admin_yayasan', 'admin_sekolah'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya Admin Sekolah yang dapat menambah data ekstrakulikuler.',
            ], 403);
        }

        $schoolId = $this->getSchoolScope($request, $user);

        if ($schoolId === -1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The selected school does not belong to your foundation.',
            ], 403);
        }

        if (!$schoolId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'School ID is required.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'nama'       => 'required|string|max:255',
            'deskripsi'  => 'nullable|string',
            'advisor_id' => 'nullable|string',
            'coach_name' => 'nullable|string|max:255',
            'status'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $advisorId = $request->input('advisor_id');
        if ($advisorId === 'none' || empty($advisorId)) {
            $advisorId = null;
        }

        $ekskul = Extracurricular::create([
            'school_id'   => $schoolId,
            'name'        => $request->input('nama'),
            'description' => $request->input('deskripsi'),
            'advisor_id'  => $advisorId,
            'coach_name'  => $request->input('coach_name'),
            'is_active'   => $this->mapStatusToDb($request->input('status', 'aktif')),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Ekstrakulikuler berhasil ditambahkan.',
            'data'    => [
                'id'             => (string) $ekskul->id,
                'nama'           => $ekskul->name,
                'deskripsi'      => $ekskul->description,
                'advisor_id'     => $ekskul->advisor_id ? (string) $ekskul->advisor_id : null,
                'pembina_nama'   => $this->formatPembinaNama($ekskul->advisor, $ekskul->advisor_id, $ekskul->coach_name),
                'coach_name'     => $ekskul->coach_name ?? '',
                'jumlah_anggota' => 0,
                'status'         => $this->mapDbToStatus($ekskul->is_active),
                'school_id'      => $ekskul->school_id,
            ],
        ], 201);
    }

    /**
     * Display the specified extracurricular.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $ekskul = Extracurricular::with(['advisor'])->find($id);

        if (!$ekskul) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ekstrakulikuler tidak ditemukan.',
            ], 404);
        }

        // Scope check
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($ekskul->school->foundation_id != $user->foundation_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($ekskul->school_id != $user->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'             => (string) $ekskul->id,
                'nama'           => $ekskul->name,
                'deskripsi'      => $ekskul->description,
                'advisor_id'     => $ekskul->advisor_id ? (string) $ekskul->advisor_id : null,
                'pembina_nama'   => $this->formatPembinaNama($ekskul->advisor, $ekskul->advisor_id, $ekskul->coach_name),
                'coach_name'     => $ekskul->coach_name ?? '',
                'jumlah_anggota' => (int) $ekskul->students()->count(),
                'status'         => $this->mapDbToStatus($ekskul->is_active),
                'school_id'      => $ekskul->school_id,
            ],
        ]);
    }

    /**
     * Update the specified extracurricular.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->hasAnyRole(['admin_yayasan', 'admin_sekolah'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya Admin Sekolah yang dapat mengubah data ekstrakulikuler.',
            ], 403);
        }

        $ekskul = Extracurricular::find($id);

        if (!$ekskul) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ekstrakulikuler tidak ditemukan.',
            ], 404);
        }

        // Scope check
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($ekskul->school->foundation_id != $user->foundation_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($ekskul->school_id != $user->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        $validator = Validator::make($request->all(), [
            'nama'       => 'sometimes|required|string|max:255',
            'deskripsi'  => 'nullable|string',
            'advisor_id' => 'nullable|string',
            'coach_name' => 'nullable|string|max:255',
            'status'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $updateData = [];
        if ($request->has('nama')) $updateData['name'] = $request->input('nama');
        if ($request->has('deskripsi')) $updateData['description'] = $request->input('deskripsi');
        if ($request->has('advisor_id')) {
            $adv = $request->input('advisor_id');
            $updateData['advisor_id'] = ($adv === 'none' || empty($adv)) ? null : $adv;
        }
        if ($request->has('coach_name')) $updateData['coach_name'] = $request->input('coach_name');
        if ($request->has('status')) $updateData['is_active'] = $this->mapStatusToDb($request->input('status'));

        $ekskul->update($updateData);

        return response()->json([
            'status'  => 'success',
            'message' => 'Ekstrakulikuler berhasil diperbarui.',
            'data'    => [
                'id'             => (string) $ekskul->id,
                'nama'           => $ekskul->name,
                'deskripsi'      => $ekskul->description,
                'advisor_id'     => $ekskul->advisor_id ? (string) $ekskul->advisor_id : null,
                'pembina_nama'   => $this->formatPembinaNama($ekskul->advisor, $ekskul->advisor_id, $ekskul->coach_name),
                'coach_name'     => $ekskul->coach_name ?? '',
                'jumlah_anggota' => (int) $ekskul->students()->count(),
                'status'         => $this->mapDbToStatus($ekskul->is_active),
                'school_id'      => $ekskul->school_id,
            ],
        ]);
    }

    /**
     * Remove the specified extracurricular.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->hasAnyRole(['admin_yayasan', 'admin_sekolah'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya Admin Sekolah yang dapat menghapus data ekstrakulikuler.',
            ], 403);
        }

        $ekskul = Extracurricular::find($id);

        if (!$ekskul) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ekstrakulikuler tidak ditemukan.',
            ], 404);
        }

        // Scope check
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($ekskul->school->foundation_id != $user->foundation_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($ekskul->school_id != $user->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        $ekskul->students()->detach();
        $ekskul->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Ekstrakulikuler berhasil dihapus.',
        ]);
    }

    /**
     * Get all enrolled students for a given extracurricular.
     */
    public function getStudents(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $ekskul = Extracurricular::find($id);

        if (!$ekskul) {
            return response()->json(['status' => 'error', 'message' => 'Ekstrakulikuler tidak ditemukan.'], 404);
        }

        // Scope check
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($ekskul->school->foundation_id != $user->foundation_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($ekskul->school_id != $user->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        $students = $ekskul->students()
            ->with(['user', 'classroom'])
            ->orderByPivot('created_at', 'desc')
            ->get();

        $formatted = $students->map(function ($profile) {
            $u = $profile->user;
            return [
                'id'                 => (string) ($u?->id ?? $profile->id),
                'student_profile_id' => $profile->id,
                'user_id'            => $u?->id,
                'nisn'               => $profile->nisn ?? '-',
                'nama'               => $u?->name ?? 'Tanpa Nama',
                'kelas'              => $profile->classroom?->name ?? 'Belum Ada Kelas',
                'jenis_kelamin'      => $profile->gender === 'male' ? 'Laki-laki' : ($profile->gender === 'female' ? 'Perempuan' : '-'),
                'join_date'          => $profile->pivot?->created_at ? $profile->pivot->created_at->format('Y-m-d') : null,
                'tanggal_bergabung'  => $profile->pivot?->created_at ? $profile->pivot->created_at->translatedFormat('d M Y') : '-',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $formatted,
        ]);
    }

    /**
     * Add/Enroll students to an extracurricular.
     */
    public function addStudents(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->hasAnyRole(['admin_yayasan', 'admin_sekolah'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $ekskul = Extracurricular::find($id);
        if (!$ekskul) {
            return response()->json(['status' => 'error', 'message' => 'Ekstrakulikuler tidak ditemukan.'], 404);
        }

        // Scope check
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($ekskul->school->foundation_id != $user->foundation_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($ekskul->school_id != $user->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        $validator = Validator::make($request->all(), [
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $rawIds = $request->input('student_ids');

        return DB::transaction(function () use ($ekskul, $rawIds) {
            // Find valid student profiles belonging to this school
            $profiles = StudentProfile::whereHas('user', function ($q) use ($ekskul) {
                $q->where('school_id', $ekskul->school_id);
            })
            ->where(function ($q) use ($rawIds) {
                $numericIds = array_filter($rawIds, fn($id) => is_numeric($id));
                $uuidIds = array_filter($rawIds, fn($id) => !is_numeric($id));

                if (!empty($numericIds) && !empty($uuidIds)) {
                    $q->whereIn('id', $numericIds)->orWhereIn('user_id', $uuidIds);
                } elseif (!empty($numericIds)) {
                    $q->whereIn('id', $numericIds);
                } else {
                    $q->whereIn('user_id', $uuidIds);
                }
            })
            ->get();

            if ($profiles->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Tidak ada siswa valid yang ditemukan untuk ditambahkan.',
                ], 422);
            }

            $profileIds = $profiles->pluck('id')->toArray();
            $ekskul->students()->syncWithoutDetaching($profileIds);

            return response()->json([
                'status'  => 'success',
                'message' => count($profileIds) . ' siswa berhasil ditambahkan ke ekstrakulikuler ' . $ekskul->name . '.',
            ]);
        });
    }

    /**
     * Remove a student from an extracurricular.
     */
    public function removeStudent(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->hasAnyRole(['admin_yayasan', 'admin_sekolah'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $ekskul = Extracurricular::find($id);
        if (!$ekskul) {
            return response()->json(['status' => 'error', 'message' => 'Ekstrakulikuler tidak ditemukan.'], 404);
        }

        // Scope check
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($ekskul->school->foundation_id != $user->foundation_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($ekskul->school_id != $user->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        $studentParam = (string) $request->input('student_id');
        if (empty($studentParam)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter student_id wajib diisi.'], 422);
        }

        $profile = StudentProfile::whereHas('user', function ($q) use ($ekskul) {
            $q->where('school_id', $ekskul->school_id);
        })
        ->where(function ($q) use ($studentParam) {
            if (is_numeric($studentParam)) {
                $q->where('id', (int) $studentParam);
            } else {
                $q->where('user_id', $studentParam);
            }
        })
        ->first();

        if (!$profile) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan.'], 404);
        }

        $ekskul->students()->detach($profile->id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Siswa berhasil dikeluarkan dari ekstrakulikuler ' . $ekskul->name . '.',
        ]);
    }

    /**
     * Get available students in this school who are not yet enrolled in this extracurricular.
     */
    public function getAvailableStudents(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $ekskul = Extracurricular::find($id);

        if (!$ekskul) {
            return response()->json(['status' => 'error', 'message' => 'Ekstrakulikuler tidak ditemukan.'], 404);
        }

        // Scope check
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($ekskul->school->foundation_id != $user->foundation_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($ekskul->school_id != $user->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        // Existing enrolled student profile IDs
        $enrolledIds = $ekskul->students()->pluck('student_profiles.id')->toArray();

        $query = StudentProfile::whereHas('user', function ($q) use ($ekskul) {
            $q->where('school_id', $ekskul->school_id)
              ->where('is_active', true)
              ->whereHas('role', fn($rq) => $rq->where('name', 'siswa'));
        })
        ->where('status', 'active')
        ->whereNotIn('id', $enrolledIds)
        ->with(['user', 'classroom']);

        if ($request->has('classroom_id') && $request->classroom_id !== 'all') {
            $query->where('classroom_id', $request->classroom_id);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nisn', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $students = $query->orderBy('nisn')->get();

        $formatted = $students->map(function ($profile) {
            $u = $profile->user;
            return [
                'id'                 => (string) ($u?->id ?? $profile->id),
                'student_profile_id' => $profile->id,
                'user_id'            => $u?->id,
                'nisn'               => $profile->nisn ?? '-',
                'nama'               => $u?->name ?? 'Tanpa Nama',
                'kelas'              => $profile->classroom?->name ?? 'Belum Ada Kelas',
                'jenis_kelamin'      => $profile->gender === 'male' ? 'Laki-laki' : ($profile->gender === 'female' ? 'Perempuan' : '-'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $formatted,
        ]);
    }
}
