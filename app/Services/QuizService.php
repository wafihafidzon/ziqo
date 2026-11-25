<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class QuizService
{
    public function find(int $id): ?Quiz
    {
        $quiz = Quiz::withCard()->find($id);
        if ($quiz) $this->attachTypes(new Collection([$quiz]));
        return $quiz;
    }

    public function canManage(Quiz $quiz, ?User $user): bool
    {
        if (!$user) return false;
        return $user->role === 'admin' || ($user->role === 'pengajar' && (int) $quiz->user_id === (int) $user->id);
    }

    /**
     * Normalisasi filter jelajah dari input request.
     *
     * @return array{0: array, 1: ?Category, 2: BaseCollection} [filter, kategori aktif, topik kategori aktif]
     */
    public function exploreFilters(array $in, bool $masuk): array
    {
        $sort = (string) ($in['sort'] ?? '');
        $f = [
            'q' => mb_substr((string) ($in['q'] ?? ''), 0, 100),
            'kategori' => (string) ($in['kategori'] ?? ''),
            'topik' => (int) ($in['topik'] ?? 0) ?: '',
            'tipe' => (string) ($in['tipe'] ?? ''),
            'kesulitan' => (string) ($in['kesulitan'] ?? ''),
            'jenjang' => (string) ($in['jenjang'] ?? ''),
            'sort' => array_key_exists($sort, Quiz::SORT) ? $sort : 'terbaru',
        ];
        if ($f['sort'] === 'rekomendasi' && !$masuk) $f['sort'] = 'populer';

        $kategori = $f['kategori'] ? Category::where('slug', $f['kategori'])->first() : null;
        if (!$kategori) $f['kategori'] = '';
        $topik = $kategori ? $kategori->topics : collect();
        if ($f['topik'] && !$topik->contains('id', (int) $f['topik'])) $f['topik'] = '';

        return [$f, $kategori, $topik];
    }

    /** Chip filter aktif: daftar [label, url tanpa filter tersebut]. */
    public function activeFilters(array $f, ?Category $kategori, BaseCollection $topik): array
    {
        $hapus = function (string $k) use ($f) {
            $q = $f;
            unset($q[$k]);
            if ($k === 'kategori') unset($q['topik']);
            return route('jelajah', array_filter($q, fn ($v) => $v !== ''));
        };
        $aktif = [];
        if ($f['q']) $aktif[] = ['“' . $f['q'] . '”', $hapus('q')];
        if ($kategori) $aktif[] = [$kategori->nama, $hapus('kategori')];
        $t = $f['topik'] ? $topik->firstWhere('id', (int) $f['topik']) : null;
        if ($t) $aktif[] = [$t->nama, $hapus('topik')];
        if (isset(TIPE_SOAL[$f['tipe']])) $aktif[] = [TIPE_SOAL[$f['tipe']], $hapus('tipe')];
        if (isset(KESULITAN[$f['kesulitan']])) $aktif[] = [KESULITAN[$f['kesulitan']], $hapus('kesulitan')];
        if (isset(JENJANG[$f['jenjang']])) $aktif[] = [JENJANG[$f['jenjang']], $hapus('jenjang')];
        return $aktif;
    }

    /** Jelajah hanya menampilkan kuis terbit yang sudah punya minimal satu soal. */
    private function exploreQuery(array $f): Builder
    {
        $tipe = $f['tipe'] ?? '';
        $kesulitan = $f['kesulitan'] ?? '';
        $jenjang = $f['jenjang'] ?? '';

        return Quiz::query()->terbit()->has('questions')
            ->when($f['q'] ?? '', fn (Builder $q, string $kata) => $q->where(fn (Builder $w) => $w
                ->where('judul', 'like', "%$kata%")
                ->orWhere('deskripsi', 'like', "%$kata%")
                ->orWhereHas('subcategory', fn (Builder $s) => $s->where('nama', 'like', "%$kata%"))))
            ->when($f['kategori'] ?? '', fn (Builder $q, string $slug) => $q->whereHas('category', fn (Builder $c) => $c->where('slug', $slug)))
            ->when($f['topik'] ?? '', fn (Builder $q, $topik) => $q->where('subcategory_id', (int) $topik))
            ->when($tipe && isset(TIPE_SOAL[$tipe]), fn (Builder $q) => $q->whereHas('questions', fn (Builder $x) => $x->where('tipe', $tipe)))
            ->when($kesulitan && isset(KESULITAN[$kesulitan]), fn (Builder $q) => $q->where('kesulitan', $kesulitan))
            ->when($jenjang && isset(JENJANG[$jenjang]), fn (Builder $q) => $q->where('jenjang', $jenjang));
    }

    public function exploreCount(array $f): int
    {
        return $this->exploreQuery($f)->count();
    }

    public function explore(array $f, int $limit, int $offset = 0, ?User $user = null): Collection
    {
        $query = $this->exploreQuery($f)->withCard();
        $sort = $f['sort'] ?? 'terbaru';

        if ($sort === 'rekomendasi' && $user) {
            $this->orderByRecommendation($query, $user);
            $query->orderByDesc('jumlah_percobaan')->orderByDesc('created_at');
        } else {
            match ($sort) {
                'populer' => $query->orderByDesc('jumlah_percobaan')->orderByDesc('created_at'),
                'judul' => $query->orderBy('judul'),
                default => $query->orderByDesc('created_at')->orderByDesc('id'),
            };
        }

        return $this->attachTypes($query->limit($limit)->offset($offset)->get());
    }

    /** +3 kategori yang pernah dikerjakan, +2 jenjang sesuai profil, -5 bila sudah pernah diselesaikan. */
    private function orderByRecommendation(Builder $query, User $user): void
    {
        $selesai = Attempt::where('user_id', $user->id)->where('status', '!=', 'berlangsung');
        $sudah = (clone $selesai)->distinct()->pluck('quiz_id')->map(fn ($id) => (int) $id)->all();
        $kategori = $sudah ? Quiz::whereKey($sudah)->distinct()->pluck('category_id')->map(fn ($id) => (int) $id)->all() : [];

        $in = fn (array $ids) => implode(',', array_fill(0, count($ids), '?'));
        $skor = 'CASE WHEN jenjang = ? THEN 2 ELSE 0 END';
        $bind = [(string) $user->jenjang];
        if ($kategori) {
            $skor .= ' + CASE WHEN category_id IN (' . $in($kategori) . ') THEN 3 ELSE 0 END';
            array_push($bind, ...$kategori);
        }
        if ($sudah) {
            $skor .= ' - CASE WHEN id IN (' . $in($sudah) . ') THEN 5 ELSE 0 END';
            array_push($bind, ...$sudah);
        }
        // Skor terhitung tidak punya padanan Eloquent; CASE kecil dengan binding.
        $query->orderByRaw("($skor) DESC", $bind);
    }

    public function recommended(User $user, int $limit = 3): Collection
    {
        return $this->explore(['sort' => 'rekomendasi'], $limit, 0, $user);
    }

    public function popular(int $limit = 6): Collection
    {
        return $this->explore(['sort' => 'populer'], $limit);
    }

    /** Kuis terbit lain di kategori yang sama (maks. 3). */
    public function related(Quiz $quiz): Collection
    {
        return $this->explore(['kategori' => $quiz->kategori_slug], 4)
            ->reject(fn (Quiz $k) => $k->is($quiz))->take(3)->values();
    }

    public function forOwner(int $userId): Collection
    {
        return $this->attachTypes(Quiz::withCard()->where('user_id', $userId)->orderByDesc('created_at')->get());
    }

    private function adminQuery(array $f): Builder
    {
        $status = $f['status'] ?? '';
        return Quiz::query()
            ->when($f['q'] ?? '', fn (Builder $q, string $kata) => $q->where(fn (Builder $w) => $w
                ->where('judul', 'like', "%$kata%")
                ->orWhereHas('owner', fn (Builder $u) => $u->where('nama', 'like', "%$kata%"))))
            ->when(in_array($status, ['draft', 'terbit'], true), fn (Builder $q) => $q->where('status', $status))
            ->when($f['kategori'] ?? '', fn (Builder $q, string $slug) => $q->whereHas('category', fn (Builder $c) => $c->where('slug', $slug)));
    }

    public function adminCount(array $f): int
    {
        return $this->adminQuery($f)->count();
    }

    public function adminList(array $f, int $limit, int $offset = 0): Collection
    {
        return $this->attachTypes(
            $this->adminQuery($f)->withCard()->orderByDesc('created_at')->limit($limit)->offset($offset)->get()
        );
    }

    /** Skor terbaik dan durasi tercepat per peserta dari percobaan yang selesai. */
    public function leaderboard(int $quizId, int $limit = 5): Collection
    {
        $selesai = fn (Builder $a) => $a->where('quiz_id', $quizId)->where('status', 'selesai');
        return User::query()->select('id', 'nama', 'avatar')
            ->whereHas('attempts', $selesai)
            ->withMax(['attempts as skor_terbaik' => $selesai], 'skor')
            ->withMin(['attempts as durasi_tercepat' => $selesai], 'durasi_detik')
            ->orderByDesc('skor_terbaik')->orderBy('durasi_tercepat')
            ->limit($limit)->get();
    }

    /** @return array{percobaan: int, peserta: int, rata: ?float, menunggu: int} */
    public function stats(int $quizId): array
    {
        $q = Attempt::where('quiz_id', $quizId)->where('status', '!=', 'berlangsung');
        $rata = (clone $q)->avg('skor');
        return [
            'percobaan' => (clone $q)->count(),
            'peserta' => (clone $q)->distinct()->count('user_id'),
            'rata' => $rata === null ? null : (float) $rata,
            'menunggu' => (clone $q)->where('status', 'menunggu')->count(),
        ];
    }

    /** Lima pengerjaan terakhir (tidak berlangsung, tidak diarsipkan) milik peserta. */
    public function recentAttempts(Quiz $quiz, User $user, int $limit = 5): Collection
    {
        return $quiz->attempts()->where('user_id', $user->id)->where('status', '!=', 'berlangsung')
            ->where('diarsipkan', false)->orderByDesc('id')->limit($limit)->get();
    }

    /** @return array{0: array, 1: array} [data, errors] */
    public function validate(array $in): array
    {
        $errors = validasi($in, [
            'judul' => 'required|min:5|max:150',
            'deskripsi' => 'nullable|max:1000',
            'petunjuk' => 'nullable|max:1000',
            'category_id' => 'required|integer',
            'kesulitan' => 'required|in:' . implode(',', array_keys(KESULITAN)),
            'jenjang' => 'required|in:' . implode(',', array_keys(JENJANG)),
            'durasi_menit' => 'required|integer|between:0,180',
        ], [
            'judul' => 'Judul', 'deskripsi' => 'Deskripsi', 'petunjuk' => 'Petunjuk', 'category_id' => 'Kategori',
            'kesulitan' => 'Tingkat kesulitan', 'jenjang' => 'Jenjang', 'durasi_menit' => 'Durasi',
        ]);
        $cat = !empty($in['category_id']) ? Category::find((int) $in['category_id']) : null;
        if (!$cat) $errors['category_id'] ??= 'Kategori tidak ditemukan.';
        $subId = !empty($in['subcategory_id']) ? (int) $in['subcategory_id'] : null;
        if ($subId) {
            $sub = Subcategory::find($subId);
            if (!$sub || !$cat || (int) $sub->category_id !== (int) $cat->id) $errors['subcategory_id'] ??= 'Topik tidak sesuai dengan kategori.';
        }
        $data = [
            'judul' => trim($in['judul'] ?? ''),
            'deskripsi' => trim($in['deskripsi'] ?? '') ?: null,
            'petunjuk' => trim($in['petunjuk'] ?? '') ?: null,
            'category_id' => (int) ($in['category_id'] ?? 0),
            'subcategory_id' => $subId,
            'kesulitan' => $in['kesulitan'] ?? 'mudah',
            'jenjang' => $in['jenjang'] ?? 'umum',
            'durasi_menit' => (int) ($in['durasi_menit'] ?? 0),
            'tampilkan_feedback' => !empty($in['tampilkan_feedback']),
            'navigasi_bebas' => !empty($in['navigasi_bebas']),
        ];
        return [$data, $errors];
    }

    public function create(array $data, User $owner): Quiz
    {
        return Quiz::create($data + ['user_id' => $owner->id, 'status' => 'draft']);
    }

    public function update(Quiz $quiz, array $data): void
    {
        $quiz->update($data);
    }

    /** Kuis hanya dapat diterbitkan bila sudah punya minimal satu soal. */
    public function publish(Quiz $quiz): bool
    {
        if (!$quiz->questions()->exists()) return false;
        $quiz->update(['status' => 'terbit']);
        return true;
    }

    public function unpublish(Quiz $quiz): void
    {
        $quiz->update(['status' => 'draft']);
    }

    /** Kuis yang sudah pernah dikerjakan (status apa pun) tidak dihapus agar riwayat peserta tetap utuh. */
    public function delete(Quiz $quiz): bool
    {
        return Quiz::whereKey($quiz->id)->whereDoesntHave('attempts')->delete() > 0;
    }

    /** `daftar_tipe`: jenis soal unik dipisah koma (null bila belum ada soal). */
    private function attachTypes(Collection $quizzes): Collection
    {
        if ($quizzes->isEmpty()) return $quizzes;
        $tipe = Question::whereIn('quiz_id', $quizzes->modelKeys())->distinct()->orderBy('tipe')
            ->get(['quiz_id', 'tipe'])->groupBy('quiz_id');
        foreach ($quizzes as $quiz) {
            $quiz->setAttribute('daftar_tipe', $tipe->get($quiz->id)?->pluck('tipe')->implode(','));
            $quiz->syncOriginalAttribute('daftar_tipe');
        }
        return $quizzes;
    }
}
