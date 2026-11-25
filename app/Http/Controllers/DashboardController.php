<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Services\BadgeService;
use App\Services\QuizService;
use App\Services\RoomService;
use App\Services\StatsService;

class DashboardController extends Controller
{
    public function __invoke(StatsService $stats, QuizService $quizzes, BadgeService $badges, RoomService $rooms)
    {
        $user = $this->user();
        $uid = $user->id;

        // Pengajar/admin tidak mengerjakan kuis, jadi dashboard mereka hanya berisi data mengajar.
        if ($user->hasRole('pengajar', 'admin')) {
            $admin = $user->hasRole('admin');
            $data = [
                'title' => 'Dashboard',
                'menu' => 'dashboard',
                'user' => $user,
                'guru' => $stats->teacher($uid),
                'esai' => $stats->pendingEssays($admin ? null : $uid, 5),
                'roomAktif' => $rooms->overview($admin ? null : $uid, ['status' => 'buka'], 5),
            ];
            if ($admin) {
                $data['platform'] = $stats->platform();
                $data['harian'] = array_map(fn ($r) => [
                    'label' => date('d/m', strtotime($r['tgl'])),
                    'nilai' => $r['n'],
                    'tip' => tanggal($r['tgl']) . ': ' . $r['n'] . ' kuis diselesaikan',
                ], $stats->dailyAttempts(14));
            }
            return view('dashboard', $data);
        }

        $stat = $stats->user($uid);
        $data = [
            'title' => 'Dashboard',
            'menu' => 'dashboard',
            'user' => $user,
            'stat' => $stat,
            'level' => $stats->level((float) $stat['xp']),
            'trend' => $stats->trend($uid)->map(fn (Attempt $r) => [
                'label' => date('d/m', strtotime((string) $r['selesai_pada'])),
                'nilai' => (float) $r['skor'],
                'tip' => $r['judul'] . ': ' . angka($r['skor']) . ' (' . tanggal((string) $r['selesai_pada']) . ')',
            ])->all(),
            'perKategori' => $stats->perCategory($uid),
            'aktivitas' => $stats->recent($uid, 6),
            'rekomendasi' => $quizzes->recommended($user, 3),
            'badges' => $badges->collection($uid)->filter(fn ($b) => $b['diperoleh_pada'])->values(),
        ];
        return view('dashboard', $data);
    }
}
