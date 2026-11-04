<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityNews;
use App\Models\User;
use App\Models\Notification;
use App\Events\NotificationSent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ActivityNewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $user->school_id;
        $foundationId = $user->foundation_id;

        // If user is from a school but foundation is not set directly
        if ($schoolId && !$foundationId) {
            $schoolObj = \App\Models\School::find($schoolId);
            if ($schoolObj) {
                $foundationId = $schoolObj->foundation_id;
            }
        }

        // If user is orang_tua, they don't have school_id, so we find schools of their children
        $schoolIds = [];
        if ($user->hasRole('orang_tua') && $user->parentProfile) {
            $schoolIds = $user->parentProfile->children()
                ->with('user')
                ->get()
                ->pluck('user.school_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        $query = ActivityNews::query();

        if (!empty($schoolIds)) {
            $query->whereIn('school_id', $schoolIds);
        } else {
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }
            if ($foundationId) {
                $query->where('foundation_id', $foundationId);
            }
        }

        $news = $query->orderBy('publish_date', 'desc')->orderBy('created_at', 'desc')->get();

        // Map to expected camelCase keys in frontend
        $mapped = $news->map(function ($item) {
            return [
                'id'       => $item->id,
                'judul'    => $item->title,
                'isi'      => $item->content,
                'kategori' => $item->category,
                'gambar'   => $item->image,
                'tanggal'  => $item->publish_date ? $item->publish_date->format('Y-m-d') : '',
                'sekolah'  => $item->school ? $item->school->name : 'SEMUA SEKOLAH',
            ];
        });

        return response()->json($mapped);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $user->school_id;
        $foundationId = $user->foundation_id;

        if ($schoolId && !$foundationId) {
            $schoolObj = \App\Models\School::find($schoolId);
            if ($schoolObj) {
                $foundationId = $schoolObj->foundation_id;
            }
        }

        if (!$schoolId || !$foundationId) {
            return response()->json([
                'message' => 'Anda tidak memiliki otorisasi sekolah/yayasan yang valid.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'judul'    => 'required|string|max:255',
            'isi'      => 'required|string',
            'kategori' => 'required|string|in:AKADEMIK,KEUANGAN,UMUM',
            'tanggal'  => 'required|date',
            'gambar'   => 'nullable|string', // Handles base64 string or image url
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $news = ActivityNews::create([
            'foundation_id' => $foundationId,
            'school_id'     => $schoolId,
            'created_by'    => $user->id,
            'title'         => $request->judul,
            'content'       => $request->isi,
            'category'      => $request->kategori,
            'image'         => $request->gambar,
            'publish_date'  => $request->tanggal,
        ]);

        // Dispatch notifications to Guru, Siswa of this school
        $schoolUsers = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['guru', 'siswa']);
        })->where('school_id', $schoolId)->get();

        // Dispatch notifications to Orang Tua of children in this school
        $parents = User::whereHas('role', function ($q) {
            $q->where('name', 'orang_tua');
        })->whereHas('parentProfile.children.user', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->get();

        $recipients = $schoolUsers->concat($parents);

        foreach ($recipients as $recipient) {
            $notification = Notification::create([
                'user_id' => $recipient->id,
                'title'   => 'Berita Kegiatan Baru: ' . $news->title,
                'content' => Str::limit(strip_tags($news->content), 150),
                'type'    => 'ACTIVITY_NEWS',
                'data'    => [
                    'activity_news_id' => $news->id,
                    'category'         => $news->category,
                    'creator_name'     => $user->name,
                ],
            ]);

            // Broadcast realtime via Pusher / Reverb
            try {
                event(new NotificationSent($notification));
            } catch (\Exception $e) {
                Log::warning('Realtime NotificationSent broadcast failed for ActivityNews: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Berita kegiatan berhasil dipublikasikan dan disiarkan.',
            'data'    => [
                'id'       => $news->id,
                'judul'    => $news->title,
                'isi'      => $news->content,
                'kategori' => $news->category,
                'gambar'   => $news->image,
                'tanggal'  => $news->publish_date ? $news->publish_date->format('Y-m-d') : '',
                'sekolah'  => $news->school ? $news->school->name : 'SEMUA SEKOLAH',
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $news = ActivityNews::find($id);

        if (!$news) {
            return response()->json(['message' => 'Berita kegiatan tidak ditemukan.'], 404);
        }

        // Security check for school context
        if ($user->school_id && (int) $news->school_id !== (int) $user->school_id) {
            // If parent, check if child is in this school
            if ($user->hasRole('orang_tua') && $user->parentProfile) {
                $hasChildInSchool = $user->parentProfile->children()
                    ->whereHas('user', function ($q) use ($news) {
                        $q->where('school_id', $news->school_id);
                    })->exists();

                if (!$hasChildInSchool) {
                    return response()->json(['message' => 'Akses ditolak.'], 403);
                }
            } else {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }
        }

        return response()->json([
            'id'       => $news->id,
            'judul'    => $news->title,
            'isi'      => $news->content,
            'kategori' => $news->category,
            'gambar'   => $news->image,
            'tanggal'  => $news->publish_date ? $news->publish_date->format('Y-m-d') : '',
            'sekolah'  => $news->school ? $news->school->name : 'SEMUA SEKOLAH',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $news = ActivityNews::find($id);

        if (!$news) {
            return response()->json(['message' => 'Berita kegiatan tidak ditemukan.'], 404);
        }

        // Only creators (admin_sekolah / kepala_sekolah) from the same school can update
        if ($user->school_id && (int) $news->school_id !== (int) $user->school_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'judul'    => 'required|string|max:255',
            'isi'      => 'required|string',
            'kategori' => 'required|string|in:AKADEMIK,KEUANGAN,UMUM',
            'tanggal'  => 'required|date',
            'gambar'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $news->update([
            'title'        => $request->judul,
            'content'      => $request->isi,
            'category'     => $request->kategori,
            'image'        => $request->gambar,
            'publish_date' => $request->tanggal,
        ]);

        return response()->json([
            'message' => 'Berita kegiatan berhasil diperbarui.',
            'data'    => [
                'id'       => $news->id,
                'judul'    => $news->title,
                'isi'      => $news->content,
                'kategori' => $news->category,
                'gambar'   => $news->image,
                'tanggal'  => $news->publish_date ? $news->publish_date->format('Y-m-d') : '',
                'sekolah'  => $news->school ? $news->school->name : 'SEMUA SEKOLAH',
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $news = ActivityNews::find($id);

        if (!$news) {
            return response()->json(['message' => 'Berita kegiatan tidak ditemukan.'], 404);
        }

        if ($user->school_id && (int) $news->school_id !== (int) $user->school_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $news->delete();

        return response()->json([
            'message' => 'Berita kegiatan berhasil dihapus.'
        ]);
    }
}
