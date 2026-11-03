<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $query = Extracurricular::query();

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
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('status') && $request->status !== 'all') {
            $isActive = $this->mapStatusToDb($request->status);
            $query->where('is_active', $isActive);
        }

        $ekskuls = $query->latest()->get();

        $formatted = $ekskuls->map(function ($item) {
            return [
                'id'        => (string) $item->id,
                'nama'      => $item->name,
                'deskripsi' => $item->description,
                'status'    => $this->mapDbToStatus($item->is_active),
                'school_id' => $item->school_id,
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

        if (!$user->isSuperAdmin() && !$user->hasRole(['admin_yayasan', 'admin_sekolah'])) {
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
            'nama'      => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $ekskul = Extracurricular::create([
            'school_id'   => $schoolId,
            'name'        => $request->input('nama'),
            'description' => $request->input('deskripsi'),
            'is_active'   => $this->mapStatusToDb($request->input('status', 'aktif')),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Extracurricular created successfully.',
            'data'    => [
                'id'        => (string) $ekskul->id,
                'nama'      => $ekskul->name,
                'deskripsi' => $ekskul->description,
                'status'    => $this->mapDbToStatus($ekskul->is_active),
                'school_id' => $ekskul->school_id,
            ],
        ], 201);
    }

    /**
     * Display the specified extracurricular.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $ekskul = Extracurricular::find($id);

        if (!$ekskul) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Extracurricular not found.',
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
                'id'        => (string) $ekskul->id,
                'nama'      => $ekskul->name,
                'deskripsi' => $ekskul->description,
                'status'    => $this->mapDbToStatus($ekskul->is_active),
                'school_id' => $ekskul->school_id,
            ],
        ]);
    }

    /**
     * Update the specified extracurricular.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->hasRole(['admin_yayasan', 'admin_sekolah'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya Admin Sekolah yang dapat mengubah data ekstrakulikuler.',
            ], 403);
        }

        $ekskul = Extracurricular::find($id);

        if (!$ekskul) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Extracurricular not found.',
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
            'nama'      => 'sometimes|required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status'    => 'nullable|string',
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
        if ($request->has('status')) $updateData['is_active'] = $this->mapStatusToDb($request->input('status'));

        $ekskul->update($updateData);

        return response()->json([
            'status'  => 'success',
            'message' => 'Extracurricular updated successfully.',
            'data'    => [
                'id'        => (string) $ekskul->id,
                'nama'      => $ekskul->name,
                'deskripsi' => $ekskul->description,
                'status'    => $this->mapDbToStatus($ekskul->is_active),
                'school_id' => $ekskul->school_id,
            ],
        ]);
    }

    /**
     * Remove the specified extracurricular.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->hasRole(['admin_yayasan', 'admin_sekolah'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya Admin Sekolah yang dapat menghapus data ekstrakulikuler.',
            ], 403);
        }

        $ekskul = Extracurricular::find($id);

        if (!$ekskul) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Extracurricular not found.',
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

        $ekskul->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Extracurricular deleted successfully.',
        ]);
    }
}
