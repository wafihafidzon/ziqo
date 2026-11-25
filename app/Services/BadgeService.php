<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Badge;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;

/** Badge bersifat permanen: kemajuan dihitung dari semua percobaan selesai, termasuk yang diarsipkan. */
class BadgeService
{
    /** Semua badge + `kategori` (nama kategori atau null), urut id. */
    public function all(): Collection
    {
        return Badge::query()
            ->select('badges.*')
            ->addSelect(['kategori' => Category::select('nama')->whereColumn('categories.id', 'badges.category_id')])
            ->orderBy('id')
            ->get();
    }

    /** Semua badge dengan `diperoleh_pada` (null bila belum) dan `kemajuan` milik pengguna. */
    public function collection(int $userId): Collection
    {
        $diperoleh = Badge::whereRelation('users', 'users.id', $userId)
            ->with(['users' => fn ($q) => $q->whereKey($userId)])
            ->get()
            ->mapWithKeys(fn (Badge $b) => [$b->id => $b->users->first()->pivot->diperoleh_pada]);

        $stat = $this->finishedStats($userId);

        return $this->all()->each(function (Badge $b) use ($stat, $diperoleh) {
            $b->setAttribute('diperoleh_pada', $diperoleh[$b->id] ?? null);
            $b->setAttribute('kemajuan', min($b->nilai_syarat, $this->progress($stat, $b)));
        });
    }

    public function countFor(int $userId): int
    {
        return Badge::whereRelation('users', 'users.id', $userId)->count();
    }

    public function earnedOn(Attempt $attempt): Collection
    {
        return $attempt->badges()->get();
    }

    /** Berikan badge yang syaratnya kini terpenuhi; mengembalikan badge baru. */
    public function evaluate(int $userId, ?int $attemptId = null): Collection
    {
        $belum = Badge::whereDoesntHave('users', fn (Builder $q) => $q->whereKey($userId))->orderBy('id')->get();
        if ($belum->isEmpty()) return $belum;
        $stat = $this->finishedStats($userId);

        return $belum->filter(function (Badge $b) use ($userId, $attemptId, $stat) {
            if ($this->progress($stat, $b) < $b->nilai_syarat) return false;
            try {
                $b->users()->attach($userId, ['attempt_id' => $attemptId, 'diperoleh_pada' => waktu()]);
                return true;
            } catch (QueryException) {
                return false;
            }
        })->values();
    }

    /**
     * Ringkasan percobaan selesai pengguna (termasuk yang diarsipkan) dalam satu query.
     * @return array{jumlah: int, sempurna: int, kategori: array<int, int>}
     */
    private function finishedStats(int $userId): array
    {
        $selesai = Attempt::where('user_id', $userId)->where('status', 'selesai')
            ->select('skor')->withAggregate('quiz as kategori_id', 'category_id')
            ->toBase()->get();

        return [
            'jumlah' => $selesai->count(),
            'sempurna' => $selesai->filter(fn ($a) => $a->skor !== null && (float) $a->skor >= 100)->count(),
            'kategori' => $selesai->countBy(fn ($a) => (int) $a->kategori_id)->all(),
        ];
    }

    /** @param array{jumlah: int, sempurna: int, kategori: array<int, int>} $stat */
    private function progress(array $stat, Badge $b): int
    {
        return match ($b->syarat) {
            'jumlah_kuis' => $stat['jumlah'],
            'skor_sempurna' => $stat['sempurna'],
            'kuis_kategori' => $b->category_id === null ? 0 : ($stat['kategori'][(int) $b->category_id] ?? 0),
            'ragam_kategori' => count($stat['kategori']),
            default => 0,
        };
    }
}
