<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\HasSchoolScope;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AcademicYearController extends Controller
{
    use HasSchoolScope;

    /**
     * Authorize if the user can access/modify the academic year.
     */
    private function authorizeAcademicYear(Request $request, AcademicYear $academicYear): bool
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->hasRole('admin_yayasan')) {
            return $academicYear->school && $academicYear->school->foundation_id == $user->foundation_id;
        }
        return $academicYear->school_id == $user->school_id;
    }

    /**
     * Display a listing of academic years.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = AcademicYear::query();

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

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $years = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data'   => $years,
        ]);
    }

    /**
     * Store a newly created academic year.
     */
    public function store(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);

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

        // Check if creating full academic year (both odd and even semesters)
        if ($request->has('odd_start_date') && $request->has('even_start_date')) {
            $validator = Validator::make($request->all(), [
                'name'            => 'required|string|max:100',
                'odd_start_date'  => 'required|date',
                'odd_end_date'    => 'required|date|after_or_equal:odd_start_date',
                'even_start_date' => 'required|date',
                'even_end_date'   => 'required|date|after_or_equal:even_start_date',
                'active_semester' => 'nullable|in:odd,even,none',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation error.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $activeSemester = $request->input('active_semester', 'odd');

            DB::beginTransaction();
            try {
                if (in_array($activeSemester, ['odd', 'even'])) {
                    AcademicYear::where('school_id', $schoolId)->update(['is_active' => false]);
                }

                $oddYear = AcademicYear::create([
                    'school_id'  => $schoolId,
                    'name'       => $request->input('name'),
                    'semester'   => 'odd',
                    'start_date' => $request->input('odd_start_date'),
                    'end_date'   => $request->input('odd_end_date'),
                    'is_active'  => ($activeSemester === 'odd'),
                ]);

                $evenYear = AcademicYear::create([
                    'school_id'  => $schoolId,
                    'name'       => $request->input('name'),
                    'semester'   => 'even',
                    'start_date' => $request->input('even_start_date'),
                    'end_date'   => $request->input('even_end_date'),
                    'is_active'  => ($activeSemester === 'even'),
                ]);

                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Tahun ajaran semester ganjil & genap berhasil dibuat.',
                    'data'    => [$oddYear, $evenYear],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Transaction failed: ' . $e->getMessage(),
                ], 500);
            }
        }

        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:100',
            'semester'   => 'required|in:odd,even',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'is_active'  => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $isActive = $request->input('is_active', false);

        DB::beginTransaction();
        try {
            if ($isActive) {
                AcademicYear::where('school_id', $schoolId)->update(['is_active' => false]);
            }

            $academicYear = AcademicYear::create([
                'school_id'  => $schoolId,
                'name'       => $request->input('name'),
                'semester'   => $request->input('semester'),
                'start_date' => $request->input('start_date'),
                'end_date'   => $request->input('end_date'),
                'is_active'  => $isActive,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Academic year created successfully.',
                'data'    => $academicYear,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified academic year.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Academic year not found.',
            ], 404);
        }

        if (!$this->authorizeAcademicYear($request, $academicYear)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $academicYear,
        ]);
    }

    /**
     * Update the specified academic year.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Academic year not found.',
            ], 404);
        }

        if (!$this->authorizeAcademicYear($request, $academicYear)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'       => 'sometimes|required|string|max:100',
            'semester'   => 'sometimes|required|in:odd,even',
            'start_date' => 'sometimes|required|date',
            'end_date'   => 'sometimes|required|date|after_or_equal:start_date',
            'is_active'  => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($request->has('is_active') && filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) === true) {
                // Deactivate other academic years with a different name for the same school
                AcademicYear::where('school_id', $academicYear->school_id)
                    ->where('name', '!=', $academicYear->name)
                    ->update(['is_active' => false]);

                // Activate all semesters (both odd and even) for this academic year
                AcademicYear::where('school_id', $academicYear->school_id)
                    ->where('name', $academicYear->name)
                    ->update(['is_active' => true]);

                if ($request->has('start_date') || $request->has('end_date') || $request->has('name')) {
                    $academicYear->update($request->only(['name', 'semester', 'start_date', 'end_date']));
                }
            } else {
                $academicYear->update($request->only(['name', 'semester', 'start_date', 'end_date', 'is_active']));
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Academic year updated successfully.',
                'data'    => $academicYear->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Academic year not found.',
            ], 404);
        }

        if (!$this->authorizeAcademicYear($request, $academicYear)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        if ($academicYear->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tahun ajaran yang sedang berstatus aktif tidak dapat dihapus.',
            ], 422);
        }

        if ($academicYear->classrooms()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak dapat menghapus tahun ajaran yang masih memiliki data kelas terkait.',
            ], 422);
        }

        if ($academicYear->schedules()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak dapat menghapus tahun ajaran yang masih memiliki data jadwal pelajaran terkait.',
            ], 422);
        }

        $academicYear->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Academic year deleted successfully.',
        ]);
    }
}
