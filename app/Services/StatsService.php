<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StatsService
{
    public function __construct(private UserService $users, private BadgeService $badges)
    {
    }

    /** Statistik pribadi: hanya percobaan selesai yang belum diarsipkan; 'menunggu' dihitung terpisah. */
    public function user(int $userId): array
    {
        $selesai = $this->personal($userId)->where('status', 'selesai')
            ->get(['skor', 'jumlah_benar', 'jumlah_salah', 'poin_didapat', 'durasi_detik']);

        return [
            'total' => $selesai->count(),
            'rata' => $selesai->avg('skor'),
            'tertinggi' => $selesai->max('skor'),
            'benar' => (int) $selesai->sum('jumlah_benar'),
            'salah' => (int) $selesai->sum('jumlah_salah'),
            'xp' => $selesai->sum(fn ($a) => (float) $a->poin_didapat),
            'waktu' => (int) $selesai->sum('durasi_detik'),
            'menunggu' => $this->personal($userId)->where('status', 'menunggu')->count(),
            'badge' => $this->badges->countFor($userId),
        ];
    }

    /** Tiap level butuh 100 XP lebih banyak dari level sebelumnya (100, 200, 300, ...). */
    public function level(float $xp): array
    {
        $level = 1;
        $butuh = 100;
        $sisa = $xp;
        while ($sisa >= $butuh) {
            $sisa -= $butuh;
            $level++;
            $butuh += 100;
        }
        return ['level' => $level, 'progres' => $sisa, 'butuh' => $butuh];
    }

    /** Percobaan selesai terakhir, urut lama → baru; item: id, skor, selesai_pada, judul. */
    public function trend(int $userId, int $limit = 12): Collection
    {
        return $this->personal($userId)->where('status', 'selesai')
            ->select(['id', 'quiz_id', 'skor', 'selesai_pada'])
            ->withAggregate('quiz as judul', 'judul')
            ->orderByDesc('selesai_pada')->orderByDesc('id')
            ->limit($limit)
            ->get()->reverse()->values();
    }

    /** Per kategori: nama, warna, jumlah, rata; urut rata tertinggi. */
    public function perCategory(int $userId): Collection
    {
        return $this->personal($userId)->where('status', 'selesai')
            ->with('quiz:id,category_id', 'quiz.category:id,nama,warna')
            ->get(['id', 'quiz_id', 'skor'])
            ->groupBy(fn (Attempt $a) => $a->quiz->category_id)
            ->map(fn (Collection $g) => [
                'nama' => $g->first()->quiz->category->nama,
                'warna' => $g->first()->quiz->category->warna,
                'jumlah' => $g->count(),
                'rata' => $g->avg('skor'),
            ])
            ->sortByDesc('rata')->values();
    }

    /** Aktivitas terbaru (semua status, belum diarsipkan) + judul, kategori, warna. */
    public function recent(int $userId, int $limit = 5): Collection
    {
        return $this->personal($userId)
            ->with('quiz:id,judul,category_id', 'quiz.category:id,nama,warna')
            // Urut waktu selesai, atau waktu mulai bila belum selesai; Eloquent tidak punya padanan COALESCE.
            ->orderByRaw('COALESCE(selesai_pada, mulai_pada) DESC')->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->each(fn (Attempt $a) => $a->setAttribute('judul', $a->quiz->judul)
                ->setAttribute('kategori', $a->quiz->category->nama)
                ->setAttribute('warna', $a->quiz->category->warna));
    }

    public function platform(): array
    {
        $selesai = Attempt::where('status', 'selesai');
        return [
            'pengguna' => $this->users->countByRole(),
            'kuis' => Quiz::where('status', 'terbit')->count(),
            'draft' => Quiz::where('status', 'draft')->count(),
            'soal' => Question::whereHas('quiz', fn (Builder $q) => $q->where('status', 'terbit'))->count(),
            'kategori' => Category::count(),
            'percobaan' => (clone $selesai)->count(),
            'rata' => $selesai->avg('skor'),
            'nonaktif' => User::where('status', 'nonaktif')->count(),
        ];
    }

    /** $hari hari terakhir (termasuk hari ini), hari tanpa pengerjaan bernilai 0. */
    public function dailyAttempts(int $hari = 14, ?int $ownerId = null): array
    {
        $dari = today()->subDays($hari - 1);
        $map = Attempt::where('status', '<>', 'berlangsung')
            ->where('selesai_pada', '>=', $dari->format('Y-m-d H:i:s'))
            ->when($ownerId, fn (Builder $q) => $q->whereHas('quiz', fn (Builder $z) => $z->where('user_id', $ownerId)))
            ->pluck('selesai_pada')
            ->countBy(fn ($t) => substr((string) $t, 0, 10));

        $out = [];
        for ($i = 0; $i < $hari; $i++) {
            $d = $dari->copy()->addDays($i)->format('Y-m-d');
            $out[] = ['tgl' => $d, 'n' => (int) ($map[$d] ?? 0)];
        }
        return $out;
    }

    /** Statistik atas semua kuis milik pengajar (termasuk percobaan yang diarsipkan peserta). */
    public function teacher(int $userId): array
    {
        $percobaan = fn () => Attempt::whereHas('quiz', fn (Builder $q) => $q->where('user_id', $userId));
        return [
            'kuis' => Quiz::where('user_id', $userId)->count(),
            'terbit' => Quiz::where('user_id', $userId)->where('status', 'terbit')->count(),
            'percobaan' => $percobaan()->where('status', '<>', 'berlangsung')->count(),
            'peserta' => $percobaan()->where('status', '<>', 'berlangsung')->distinct()->count('user_id'),
            'menunggu' => $percobaan()->where('status', 'menunggu')->count(),
            'rata' => $percobaan()->where('status', 'selesai')->avg('skor'),
        ];
    }

    /** Percobaan menunggu penilaian esai, paling lama dikumpulkan dulu; item: id, selesai_pada, quiz_id, judul, nama. */
    public function pendingEssays(?int $ownerId, int $limit = 5): Collection
    {
        return $this->pendingQuery($ownerId)
            ->select(['id', 'selesai_pada', 'quiz_id', 'user_id'])
            ->withAggregate('quiz as judul', 'judul')
            ->withAggregate('user as nama', 'nama')
            ->orderBy('selesai_pada')
            ->limit($limit)
            ->get();
    }

    /** Jumlah percobaan menunggu penilaian esai (filter sama dengan pendingEssays). */
    public function pendingEssayCount(?int $ownerId): int
    {
        return $this->pendingQuery($ownerId)->count();
    }

    private function pendingQuery(?int $ownerId): Builder
    {
        return Attempt::where('status', 'menunggu')
            ->when($ownerId, fn (Builder $q) => $q->whereHas('quiz', fn (Builder $z) => $z->where('user_id', $ownerId)));
    }

    private function personal(int $userId): Builder
    {
        return Attempt::where('user_id', $userId)->where('diarsipkan', false);
    }
}
