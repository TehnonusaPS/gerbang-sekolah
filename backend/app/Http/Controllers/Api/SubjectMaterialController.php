<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectMaterialRequest;
use App\Models\SubjectMaterial;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubjectMaterialController extends Controller
{
    /**
     * Display a listing of materials.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = SubjectMaterial::with(['classroom:id,name', 'subject:id,name,code', 'academicYear:id,name,semester'])
            ->where('school_id', $user->school_id);

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->has('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->get(),
        ]);
    }

    /**
     * Store a newly created material in storage.
     */
    public function store(StoreSubjectMaterialRequest $request): JsonResponse
    {
        $user = $request->user();

        // 1. Verify that teacher is assigned to this subject and classroom
        $assigned = TeacherSubjectAssignment::where('teacher_id', $user->id)
            ->where('subject_id', $request->subject_id)
            ->where('classroom_id', $request->classroom_id)
            ->where('is_active', true)
            ->exists();

        if (!$assigned) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki hak akses untuk mengajar mata pelajaran ini di kelas yang dipilih.',
            ], 403);
        }

        // 2. Upload file
        if (!$request->hasFile('file')) {
            return response()->json([
                'status' => 'error',
                'message' => 'File materi wajib diunggah.',
            ], 422);
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileType = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        // Path: public/materials/{school_id}/{subject_id}/filename_timestamp.ext
        $storedName = time() . '_' . str_replace(' ', '_', $fileName);
        $path = $file->storeAs("materials/{$user->school_id}/{$request->subject_id}", $storedName, 'public');

        // 3. Create record
        $material = SubjectMaterial::create([
            'school_id'        => $user->school_id,
            'subject_id'       => $request->subject_id,
            'classroom_id'     => $request->classroom_id,
            'academic_year_id' => $request->academic_year_id,
            'uploaded_by'      => $user->id,
            'title'            => $request->title,
            'file_path'        => $path,
            'file_name'        => $fileName,
            'file_type'        => $fileType,
            'file_size'        => $fileSize,
            'uploaded_by_name' => $user->name,
            'is_active'        => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Materi berhasil diunggah.',
            'data' => $material,
        ], 201);
    }

    /**
     * Display the specified material.
     */
    public function show(string $id): JsonResponse
    {
        $material = SubjectMaterial::with(['classroom', 'subject', 'academicYear'])->find($id);

        if (!$material) {
            return response()->json([
                'status' => 'error',
                'message' => 'Materi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $material,
        ]);
    }

    /**
     * Update the specified material in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $material = SubjectMaterial::find($id);

        if (!$material) {
            return response()->json([
                'status' => 'error',
                'message' => 'Materi tidak ditemukan.',
            ], 404);
        }

        // Verify ownership/school access
        if ($material->school_id !== $user->school_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $rules = [
            'title'            => 'required|string|max:255',
            'file'             => 'nullable|file|mimes:pdf,ppt,pptx|max:15360',
            'is_active'        => 'nullable|boolean',
        ];

        $request->validate($rules);

        $data = [
            'title' => $request->title,
        ];

        if ($request->has('is_active')) {
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }

        // If new file is uploaded, replace the old one
        if ($request->hasFile('file')) {
            // Delete old file
            if (Storage::disk('public')->exists($material->file_path)) {
                Storage::disk('public')->delete($material->file_path);
            }

            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileType = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();

            $storedName = time() . '_' . str_replace(' ', '_', $fileName);
            $path = $file->storeAs("materials/{$user->school_id}/{$material->subject_id}", $storedName, 'public');

            $data['file_path'] = $path;
            $data['file_name'] = $fileName;
            $data['file_type'] = $fileType;
            $data['file_size'] = $fileSize;
        }

        $material->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Materi berhasil diupdate.',
            'data' => $material,
        ]);
    }

    /**
     * Remove the specified material from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $material = SubjectMaterial::find($id);

        if (!$material) {
            return response()->json([
                'status' => 'error',
                'message' => 'Materi tidak ditemukan.',
            ], 404);
        }

        // Delete actual file
        if (Storage::disk('public')->exists($material->file_path)) {
            Storage::disk('public')->delete($material->file_path);
        }

        $material->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Materi berhasil dihapus.',
        ]);
    }

    /**
     * Download the specified material file.
     */
    public function download(string $id)
    {
        $material = SubjectMaterial::find($id);

        if (!$material) {
            return response()->json([
                'status' => 'error',
                'message' => 'Materi tidak ditemukan.',
            ], 404);
        }

        if (!Storage::disk('public')->exists($material->file_path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File fisik tidak ditemukan di server.',
            ], 404);
        }

        return Storage::disk('public')->download($material->file_path, $material->file_name);
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(string $id): JsonResponse
    {
        $material = SubjectMaterial::find($id);

        if (!$material) {
            return response()->json([
                'status' => 'error',
                'message' => 'Materi tidak ditemukan.',
            ], 404);
        }

        $material->is_active = !$material->is_active;
        $material->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status materi berhasil diubah.',
            'data' => [
                'is_active' => $material->is_active,
            ],
        ]);
    }
}
