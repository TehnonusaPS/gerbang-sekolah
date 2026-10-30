<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffAttendanceController extends Controller
{
    /**
     * Store clock-in attendance.
     */
    public function clockIn(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terikat dengan sekolah manapun.'
            ], 403);
        }

        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo'     => 'required|image|max:5120', // max 5MB
        ]);

        $today = Carbon::today()->toDateString();

        // Check if already clocked in today
        $existing = StaffAttendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan absensi hari ini.'
            ], 422);
        }

        // Get school settings
        $school = $user->school;
        $threshold = $school->attendance_late_threshold ?? '07:30:00';

        $now = Carbon::now();
        $currentTime = $now->toTimeString();

        // Compare time
        $status = ($currentTime > $threshold) ? 'Terlambat' : 'Hadir';

        // Store photo
        $path = $request->file('photo')->store('attendance', 'public');

        $attendance = StaffAttendance::create([
            'user_id'    => $user->id,
            'school_id'  => $user->school_id,
            'date'       => $today,
            'time_in'    => $currentTime,
            'status'     => $status,
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
            'image_path' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Absensi berhasil disimpan. Status: {$status}.",
            'data'    => [
                'id'         => $attendance->id,
                'date'       => Carbon::parse($attendance->date)->translatedFormat('d F Y'),
                'time_in'    => Carbon::parse($attendance->time_in)->format('H:i'),
                'status'     => $attendance->status,
                'image_url'  => Storage::disk('public')->url($attendance->image_path),
            ]
        ]);
    }

    /**
     * Get attendance history for the logged-in user.
     */
    public function myHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Retrieve last 30 days of records
        $attendances = StaffAttendance::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        $leaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get();

        // Map them together
        $history = [];

        // Compile logs
        foreach ($attendances as $att) {
            $history[] = [
                'id' => 'att_' . $att->id,
                'tanggal' => Carbon::parse($att->date)->translatedFormat('d F Y'),
                'jamMasuk' => Carbon::parse($att->time_in)->format('H:i'),
                'status' => $att->status,
                'type' => 'attendance'
            ];
        }

        foreach ($leaves as $leave) {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                // If there's already an attendance on this day, skip it
                $dateStr = $d->toDateString();
                if ($attendances->contains('date', $dateStr)) {
                    continue;
                }

                $history[] = [
                    'id' => 'leave_' . $leave->id . '_' . $dateStr,
                    'tanggal' => $d->translatedFormat('d F Y'),
                    'jamMasuk' => '-',
                    'status' => $leave->type, // 'Izin' or 'Cuti'
                    'type' => 'leave'
                ];
            }
        }

        // Sort by parsed date descending
        usort($history, function($a, $b) {
            return strtotime($b['tanggal']) <=> strtotime($a['tanggal']);
        });

        // Slice to max 30 items
        $history = array_slice($history, 0, 30);

        // Get monthly summary stats
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $hadirCount = StaffAttendance::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->where('status', 'Hadir')
            ->count();

        $terlambatCount = StaffAttendance::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->where('status', 'Terlambat')
            ->count();

        $izinCutiCount = 0;
        foreach ($leaves as $leave) {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                if ($d->month == $currentMonth && $d->year == $currentYear) {
                    $izinCutiCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'history' => $history,
                'summary' => [
                    'hadir' => $hadirCount,
                    'terlambat' => $terlambatCount,
                    'izin_cuti' => $izinCutiCount,
                ]
            ]
        ]);
    }

    /**
     * Submit leave request.
     */
    public function submitLeaveRequest(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terikat dengan sekolah manapun.'
            ], 403);
        }

        $request->validate([
            'type'       => 'required|in:Izin,Cuti',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:1000',
        ]);

        $leave = LeaveRequest::create([
            'user_id'    => $user->id,
            'school_id'  => $user->school_id,
            'type'       => $request->type,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'reason'     => $request->reason,
            'status'     => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permintaan izin/cuti berhasil diajukan dan sedang menunggu persetujuan.',
            'data'    => $leave
        ]);
    }

    /**
     * Get leave requests submitted by current user.
     */
    public function myLeaveRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $leaves = LeaveRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $leaves
        ]);
    }
}
