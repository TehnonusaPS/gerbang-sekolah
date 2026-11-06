<?php

namespace App\Http\Controllers\Api\Yayasan;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use App\Models\School;
use App\Models\StudentBill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get aggregate stats, unit comparison, and recent activities for Admin Yayasan Dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $foundationId = $user->foundation_id;

        if (!$foundationId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Anda belum terasosiasi dengan yayasan manapun.'
            ], 403);
        }

        $foundation = Foundation::find($foundationId);
        $schools = School::where('foundation_id', $foundationId)->get();
        $schoolIds = $schools->pluck('id')->toArray();

        $startOfThisMonth = now()->startOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth   = now()->subMonth()->endOfMonth();

        // 1. Total Siswa Konsolidasi
        $siswaQuery = User::whereIn('school_id', $schoolIds)->whereHas('role', fn($q) => $q->where('name', 'siswa'));
        $totalSiswa = (clone $siswaQuery)->count();
        $siswaThisMonth = (clone $siswaQuery)->where('created_at', '>=', $startOfThisMonth)->count();
        $siswaLastMonth = (clone $siswaQuery)->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $siswaGrowth = $this->calculateGrowth($siswaThisMonth, $siswaLastMonth);

        // 2. Unit Sekolah Binaan
        $totalSekolah = $schools->count();
        $sekolahAktif = $schools->where('status', 'active')->count();
        $sekolahNonAktif = $totalSekolah - $sekolahAktif;
        $akreditasiCounts = $schools->groupBy('accreditation')->map->count();
        $akredList = [];
        foreach ($akreditasiCounts as $akred => $count) {
            if ($akred) {
                $akredList[] = "{$count} Akreditasi {$akred}";
            }
        }
        $akreditasiSummary = !empty($akredList) ? implode(' • ', $akredList) : 'Semua Unit Terdaftar';

        // 3. Pendidik & Staf (PTK)
        $guruQuery = User::whereIn('school_id', $schoolIds)->whereHas('role', fn($q) => $q->whereIn('name', ['guru', 'wali_kelas']));
        $staffQuery = User::whereIn('school_id', $schoolIds)->whereHas('role', fn($q) => $q->whereIn('name', ['tata_usaha', 'admin_sekolah', 'kepala_sekolah']));
        $totalGuru = (clone $guruQuery)->count();
        $totalStaff = (clone $staffQuery)->count();
        $totalPtk = $totalGuru + $totalStaff;
        $ratioText = $totalGuru > 0 && $totalSiswa > 0 ? '1:' . round($totalSiswa / $totalGuru) : '1:0';

        // 4. Kolektibilitas Keuangan / Tagihan SPP
        $bills = StudentBill::whereHas('student', fn($q) => $q->whereIn('school_id', $schoolIds))->get();
        $totalTagihan  = (float) $bills->sum('amount');
        $totalTerbayar = (float) $bills->sum('paid_amount');
        $totalTunggakan = max(0, $totalTagihan - $totalTerbayar);
        $kolektibilitasPct = $totalTagihan > 0 ? round(($totalTerbayar / $totalTagihan) * 100, 1) : 100.0;

        // 5. Komparasi Kinerja Per Unit Sekolah
        $schoolComparison = $schools->map(function ($sch) {
            $siswaCount = User::where('school_id', $sch->id)->whereHas('role', fn($q) => $q->where('name', 'siswa'))->count();
            $guruCount  = User::where('school_id', $sch->id)->whereHas('role', fn($q) => $q->whereIn('name', ['guru', 'wali_kelas']))->count();
            $staffCount = User::where('school_id', $sch->id)->whereHas('role', fn($q) => $q->whereIn('name', ['tata_usaha', 'admin_sekolah', 'kepala_sekolah']))->count();
            
            $schBills = StudentBill::whereHas('student', fn($q) => $q->where('school_id', $sch->id))->get();
            $schTagihan = (float) $schBills->sum('amount');
            $schTerbayar = (float) $schBills->sum('paid_amount');
            $schKolektibilitas = $schTagihan > 0 ? round(($schTerbayar / $schTagihan) * 100, 1) : 100.0;

            // Nama ringkas untuk chart (misal: "SD Nusantara Pintar Bekasi" -> "SD Nusantara")
            $shortName = $sch->level ? $sch->level . ' ' . explode(' ', $sch->name)[1] ?? $sch->name : $sch->name;

            return [
                'id'              => $sch->id,
                'name'            => $shortName,
                'fullName'        => $sch->name,
                'level'           => $sch->level ?? 'Umum',
                'accreditation'   => $sch->accreditation ?? '-',
                'status'          => $sch->status ?? 'active',
                'siswa'           => $siswaCount,
                'guru'            => $guruCount,
                'staff'           => $staffCount,
                'totalPtk'        => $guruCount + $staffCount,
                'tagihan'         => $schTagihan,
                'terbayar'        => $schTerbayar,
                'tunggakan'       => max(0, $schTagihan - $schTerbayar),
                'kolektibilitas'  => $schKolektibilitas,
            ];
        })->values()->toArray();

        // 6. Aktivitas / Peristiwa Terbaru Lintas Sekolah di Yayasan Ini
        $activities = $this->getYayasanRecentActivities($schoolIds);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'foundation' => [
                    'id'   => $foundation->id,
                    'name' => $foundation->name,
                ],
                'stats' => [
                    'siswa' => [
                        'total'          => $totalSiswa,
                        'newThisMonth'   => $siswaThisMonth,
                        'growthPercent'  => $siswaGrowth['percent'],
                        'trendDirection' => $siswaGrowth['direction'],
                        'subText'        => "di seluruh unit sekolah",
                    ],
                    'sekolah' => [
                        'total'          => $totalSekolah,
                        'aktif'          => $sekolahAktif,
                        'nonAktif'       => $sekolahNonAktif,
                        'akreditasi'     => $akreditasiSummary,
                        'subText'        => "unit sekolah aktif",
                    ],
                    'ptk' => [
                        'total'          => $totalPtk,
                        'guru'           => $totalGuru,
                        'staff'          => $totalStaff,
                        'ratio'          => $ratioText,
                        'subText'        => "Rasio Guru:Siswa {$ratioText}",
                    ],
                    'keuangan' => [
                        'totalTagihan'         => $totalTagihan,
                        'totalTerbayar'        => $totalTerbayar,
                        'totalTunggakan'       => $totalTunggakan,
                        'kolektibilitasPercent' => $kolektibilitasPct,
                        'trendDirection'       => $kolektibilitasPct >= 75 ? 'up' : 'down',
                        'subText'              => "Tingkat kepatuhan pembayaran SPP",
                    ],
                ],
                'school_comparison' => $schoolComparison,
                'activities'        => $activities,
            ]
        ]);
    }

    private function calculateGrowth(int $current, int $previous): array
    {
        if ($previous == 0) {
            return [
                'percent'   => $current > 0 ? 100.0 : 0.0,
                'direction' => 'up',
            ];
        }

        $diff = $current - $previous;
        $pct  = round(($diff / $previous) * 100, 1);

        return [
            'percent'   => abs($pct),
            'direction' => $pct >= 0 ? 'up' : 'down',
        ];
    }

    private function getYayasanRecentActivities(array $schoolIds): array
    {
        $items = collect();

        // 1. Siswa terbaru
        $recentStudents = User::whereIn('school_id', $schoolIds)
            ->whereHas('role', fn($q) => $q->where('name', 'siswa'))
            ->with('school:id,name')
            ->latest()
            ->take(3)
            ->get();

        foreach ($recentStudents as $std) {
            $sName = $std->school ? $std->school->name : 'Unit Sekolah';
            $items->push([
                'title'      => $sName,
                'desc'       => "Pendaftaran siswa baru: {$std->name}",
                'type'       => 'add',
                'created_at' => $std->created_at ?? now(),
            ]);
        }

        // 2. Guru/Staf terbaru
        $recentTeachers = User::whereIn('school_id', $schoolIds)
            ->whereHas('role', fn($q) => $q->whereIn('name', ['guru', 'wali_kelas', 'tata_usaha']))
            ->with('school:id,name')
            ->latest()
            ->take(2)
            ->get();

        foreach ($recentTeachers as $tch) {
            $sName = $tch->school ? $tch->school->name : 'Unit Sekolah';
            $items->push([
                'title'      => $sName,
                'desc'       => "Penugasan tenaga pendidik: {$tch->name}",
                'type'       => 'update',
                'created_at' => $tch->created_at ?? now(),
            ]);
        }

        $sorted = $items->sortByDesc('created_at')->take(5)->values();

        return $sorted->map(function ($item, $idx) {
            $timeString = 'Baru saja';
            if ($item['created_at']) {
                $c = Carbon::parse($item['created_at']);
                $diffMinutes = (int) $c->diffInMinutes();
                $diffHours   = (int) $c->diffInHours();
                $diffDays    = (int) $c->diffInDays();

                if ($diffMinutes < 1) {
                    $timeString = 'Baru saja';
                } elseif ($diffMinutes < 60) {
                    $timeString = "{$diffMinutes} menit lalu";
                } elseif ($diffHours < 24) {
                    $timeString = "{$diffHours} jam lalu";
                } elseif ($diffDays === 1) {
                    $timeString = "Kemarin, " . $c->format('H:i');
                } else {
                    $timeString = "{$diffDays} hari lalu";
                }
            }

            return [
                'id'    => $idx + 1,
                'title' => $item['title'],
                'desc'  => $item['desc'],
                'time'  => $timeString,
                'type'  => $item['type'],
            ];
        })->toArray();
    }
}

