<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\UserAnswer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AttemptService
{
    /** Toleransi (detik) setelah batas waktu sebelum percobaan dianggap kedaluwarsa. */
    public const GRACE = 15;

    public function __construct(private BadgeService $badges)
    {
    }

    public function ongoing(int $quizId, int $userId): ?Attempt
    {
        return Attempt::where('quiz_id', $quizId)->where('user_id', $userId)
            ->where('status', 'berlangsung')->latest('id')->first();
    }

    public function ownedBy(int $attemptId, int $userId): ?Attempt
    {
        return Attempt::whereKey($attemptId)->where('user_id', $userId)->first();
    }

    /**
     * Lanjutkan percobaan yang masih berjalan; yang sudah lewat waktu dikumpulkan dulu lalu dibuat baru.
     * $roomId: percobaan masuk lewat room. Percobaan berjalan yang dilanjutkan ikut dipindah ke room itu
     * agar muncul di daftar peserta room tanpa membuang jawaban yang sudah tersimpan.
     */
    public function start(Quiz $quiz, int $userId, ?int $roomId = null): Attempt
    {
        $ada = $this->ongoing($quiz->id, $userId);
        if ($ada) {
            if (!$this->isExpired($ada, $quiz)) {
                if ($roomId !== null && (int) $ada->room_id !== $roomId) $ada->update(['room_id' => $roomId]);
                return $ada;
            }
            $this->finish($ada);
        }

        return Attempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userId,
            'room_id' => $roomId,
            'status' => 'berlangsung',
            'mulai_pada' => waktu(),
        ]);
    }

    public function deadline(Attempt $attempt, Quiz $quiz): ?int
    {
        $menit = (int) $quiz['durasi_menit'];
        return $menit > 0 ? strtotime($attempt->mulai_pada) + $menit * 60 : null;
    }

    public function isExpired(Attempt $attempt, Quiz $quiz): bool
    {
        $batas = $this->deadline($attempt, $quiz);
        return $batas !== null && now()->getTimestamp() > $batas + self::GRACE;
    }

    /** Soal kuis (urutan, id) beserta opsi (urutan, id). */
    public function questions(Quiz $quiz): Collection
    {
        return $quiz->questions()->with('answerOptions')->get();
    }

    public function question(Quiz $quiz, int $questionId): ?Question
    {
        return $quiz->questions()->whereKey($questionId)->first();
    }

    /** Jawaban tersimpan per question_id: id, teks, opsi (id int), dikunci, benar, poin, catatan. */
    public function savedAnswers(Attempt $attempt): array
    {
        return $attempt->answers()->with('selectedOptions')->get()
            ->mapWithKeys(fn (UserAnswer $a) => [$a->question_id => $this->answerData($a)])
            ->all();
    }

    private function answerData(UserAnswer $a): array
    {
        return [
            'id' => (int) $a->id,
            'teks' => $a->jawaban_teks,
            'opsi' => $a->selectedOptions->map(fn ($o) => (int) $o->id)->sort()->values()->all(),
            'dikunci' => (int) $a->dikunci,
            'benar' => $a->benar,
            'poin' => $a->poin,
            'catatan' => $a->catatan,
        ];
    }

    /** Data untuk player JS: soal tanpa kunci, jawaban tersimpan, umpan balik soal terkunci, sisa waktu. */
    public function playConfig(Attempt $attempt, Quiz $quiz): array
    {
        $soal = $this->questions($quiz);
        $feedback = (bool) $quiz['tampilkan_feedback'];
        $perId = $soal->keyBy('id');

        $jawaban = [];
        $umpan = [];
        foreach ($this->savedAnswers($attempt) as $qid => $j) {
            $jawaban[$qid] = ['opsi' => $j['opsi'], 'teks' => $j['teks'], 'dikunci' => (bool) $j['dikunci']];
            if ($feedback && $j['dikunci'] && $perId->has($qid) && $perId[$qid]->tipe !== 'esai') {
                $umpan[$qid] = $this->feedback($perId[$qid], $j);
            }
        }
        $batas = $this->deadline($attempt, $quiz);

        return [
            'soal' => $soal->map(fn (Question $s) => [
                'id' => (int) $s->id,
                'tipe' => $s->tipe,
                'teks' => $s->teks,
                'poin' => (int) $s->poin,
                'opsi' => in_array($s->tipe, ['pg', 'pgk', 'bs'], true)
                    ? $s->answerOptions->map(fn ($o) => ['id' => (int) $o->id, 'teks' => $o->teks])->all()
                    : [],
            ])->all(),
            'jawaban' => (object) $jawaban,
            'umpan' => (object) $umpan,
            'feedback' => $feedback,
            'bebas' => (bool) $quiz['navigasi_bebas'],
            'sisaDetik' => $batas ? max(0, $batas - now()->getTimestamp()) : null,
        ];
    }

    /** @return string|null pesan galat, null bila tersimpan */
    public function saveAnswer(Attempt $attempt, Quiz $quiz, Question $soal, $nilai, bool $kunci = false): ?string
    {
        if ($attempt->status !== 'berlangsung') return 'Percobaan ini sudah selesai.';
        if ($this->isExpired($attempt, $quiz)) return 'Waktu pengerjaan sudah habis.';

        $lama = $attempt->answers()->where('question_id', $soal->id)->first();
        if ($lama?->dikunci) return 'Jawaban soal ini sudah dikunci.';

        $teks = null;
        $opsiDipilih = [];
        if (in_array($soal->tipe, ['pg', 'bs', 'pgk'], true)) {
            $valid = $soal->answerOptions()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $opsiDipilih = array_values(array_unique(array_map('intval', (array) $nilai)));
            if (array_diff($opsiDipilih, $valid)) return 'Opsi jawaban tidak valid.';
            if ($soal->tipe !== 'pgk' && count($opsiDipilih) > 1) return 'Soal ini hanya menerima satu jawaban.';
        } else {
            $teks = is_string($nilai) ? trim($nilai) : '';
            $maks = $soal->tipe === 'esai' ? 5000 : 200;
            if (mb_strlen($teks) > $maks) return "Jawaban maksimal $maks karakter.";
            if ($teks === '') $teks = null;
        }

        DB::transaction(function () use ($lama, $attempt, $soal, $teks, $opsiDipilih, $kunci) {
            $jawaban = $lama ?? new UserAnswer(['attempt_id' => $attempt->id, 'question_id' => $soal->id]);
            $jawaban->fill(['jawaban_teks' => $teks, 'dikunci' => $kunci])->save();
            $jawaban->selectedOptions()->sync($opsiDipilih);
        });
        return null;
    }

    /** Mode berurutan: soal sebelum $questionId dikunci; yang dilewati dicatat sebagai jawaban kosong terkunci. */
    public function lockBefore(Attempt $attempt, Quiz $quiz, int $questionId): void
    {
        $urut = $quiz->questions()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $pos = array_search($questionId, $urut, true);
        if ($pos === false || $pos === 0) return;

        $sebelum = array_slice($urut, 0, $pos);
        $attempt->answers()->whereIn('question_id', $sebelum)->update(['dikunci' => true]);
        $ada = $attempt->answers()->whereIn('question_id', $sebelum)->pluck('question_id')->map(fn ($id) => (int) $id)->all();
        foreach (array_diff($sebelum, $ada) as $qid) {
            $attempt->answers()->create(['question_id' => $qid, 'dikunci' => true]);
        }
    }

    /** Isian: longgar = abaikan huruf besar & tanda '.'/'!' di akhir; ketat = hanya rapikan spasi. */
    public function normalisasi(string $s, string $mode): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', $s));
        return $mode === 'ketat' ? $s : rtrim(mb_strtolower($s), '.!');
    }

    /**
     * PG kompleks benar hanya bila pilihan sama persis dengan kunci; esai menunggu penilaian manual.
     * @return array{0: int|null, 1: float|null} [benar, poin]
     */
    public function grade(Question $soal, ?array $jawaban): array
    {
        if ($soal->tipe === 'esai') return [null, null];
        $opsi = $soal->answerOptions;
        $benar = false;
        if ($jawaban) {
            if ($soal->tipe === 'isian') {
                $j = $this->normalisasi((string) $jawaban['teks'], $soal->pencocokan);
                foreach ($opsi as $o) {
                    if ($j !== '' && $j === $this->normalisasi($o->teks, $soal->pencocokan)) { $benar = true; break; }
                }
            } else {
                $kunci = Attempt::kunci($opsi);
                $pilih = $jawaban['opsi'];
                sort($kunci);
                sort($pilih);
                $benar = $pilih !== [] && $pilih === $kunci;
            }
        }
        return [$benar ? 1 : 0, $benar ? (float) $soal->poin : 0.0];
    }

    public function feedback(Question $soal, ?array $jawaban): array
    {
        [$benar] = $this->grade($soal, $jawaban);
        return [
            'benar' => (bool) $benar,
            'kunci' => Attempt::kunci($soal->answerOptions),
            'jawaban_diterima' => $soal->tipe === 'isian' ? $soal->answerOptions->pluck('teks')->all() : [],
            'pembahasan' => $soal->pembahasan,
        ];
    }

    /** Aksi "cek": simpan + kunci jawaban, lalu kembalikan umpan balik. */
    public function check(Attempt $attempt, Quiz $quiz, Question $soal, $nilai): array
    {
        if (!(int) $quiz['tampilkan_feedback']) return ['ok' => false, 'pesan' => 'Kuis ini tidak menampilkan umpan balik langsung.'];
        if ($soal->tipe === 'esai') return ['ok' => false, 'pesan' => 'Soal esai dinilai oleh pengajar.'];
        if ($err = $this->saveAnswer($attempt, $quiz, $soal, $nilai, true)) return ['ok' => false, 'pesan' => $err];

        $soal->load('answerOptions');
        $jawaban = $this->savedAnswers($attempt)[$soal->id] ?? null;
        return ['ok' => true] + $this->feedback($soal, $jawaban);
    }

    /** Kumpulkan: nilai semua soal, kunci jawaban, catat waktu selesai (maksimal = batas waktu). */
    public function finish(Attempt $attempt): Attempt
    {
        return DB::transaction(function () use ($attempt) {
            $att = Attempt::find($attempt->id);
            if (!$att || $att->status !== 'berlangsung') return $att ?? $attempt;

            $quiz = $att->quiz;
            $jawaban = $att->answers()->with('selectedOptions')->get()->keyBy('question_id');

            // Satu upsert: jawaban yang ada diperbarui, soal tanpa jawaban dibuat (benar 0, poin 0).
            $baris = [];
            foreach ($this->questions($quiz) as $s) {
                $model = $jawaban->get($s->id);
                $j = $model ? $this->answerData($model) : null;
                [$benar, $poin] = $this->grade($s, $j);
                if ($s->tipe === 'esai' && trim((string) ($j['teks'] ?? '')) === '') { $benar = 0; $poin = 0.0; }
                $baris[] = ['attempt_id' => $att->id, 'question_id' => $s->id, 'benar' => $benar, 'poin' => $poin, 'dikunci' => true];
            }
            UserAnswer::upsert($baris, ['attempt_id', 'question_id'], ['benar', 'poin', 'dikunci']);

            $mulai = strtotime($att->mulai_pada);
            $batas = $this->deadline($att, $quiz);
            $sekarang = now()->getTimestamp();
            $selesai = $batas ? min($sekarang, $batas) : $sekarang;
            $att->update([
                'selesai_pada' => date('Y-m-d H:i:s', $selesai),
                'durasi_detik' => max(0, $selesai - $mulai),
            ]);
            return $this->recalculate($att);
        });
    }

    /** Hitung ulang skor; status 'menunggu' selama ada jawaban tanpa poin (esai belum dinilai). */
    public function recalculate(Attempt $attempt): Attempt
    {
        $maks = (float) Question::whereIn('id', $attempt->answers()->select('question_id'))->sum('poin');
        $didapat = (float) $attempt->answers()->sum('poin');
        $menunggu = $attempt->answers()->whereNull('poin')->exists();

        $attempt->update([
            'poin_maks' => $maks,
            'poin_didapat' => $didapat,
            'jumlah_benar' => $attempt->answers()->where('benar', true)->count(),
            'jumlah_salah' => $attempt->answers()->where('benar', false)->count(),
            'status' => $menunggu ? 'menunggu' : 'selesai',
            'skor' => $menunggu ? null : ($maks > 0 ? round($didapat / $maks * 100, 2) : 0),
        ]);
        if (!$menunggu) $this->badges->evaluate($attempt->user_id, $attempt->id);
        return $attempt;
    }

    /** Esai dianggap benar bila mendapat minimal setengah poin. */
    public function gradeEssay(UserAnswer $answer, float $poin, ?string $catatan, int $penilaiId): Attempt
    {
        return DB::transaction(function () use ($answer, $poin, $catatan, $penilaiId) {
            $this->scoreEssay($answer, $poin, $catatan, $penilaiId);
            return $this->recalculate($answer->attempt);
        });
    }

    private function scoreEssay(UserAnswer $answer, float $poin, ?string $catatan, int $penilaiId): void
    {
        $answer->update([
            'poin' => $poin,
            'benar' => $poin >= ((float) $answer->question->poin) / 2,
            'catatan' => $catatan,
            'dinilai_oleh' => $penilaiId,
        ]);
    }

    /**
     * Validasi lalu simpan nilai esai dari form pengajar. Input kosong dilewati.
     * @return array<int, string> galat per id jawaban; kosong = tersimpan
     */
    public function gradeEssays(Attempt $attempt, array $poinInput, array $catatanInput, int $penilaiId): array
    {
        $esai = $attempt->answers()->with('question')->orderBy('id')
            ->whereRelation('question', 'tipe', 'esai')->get();

        $errors = [];
        $simpan = [];
        foreach ($esai as $jawaban) {
            $maks = $jawaban->question->poin;
            $raw = $poinInput[$jawaban->id] ?? null;
            if ($raw === null || $raw === '') continue;
            if (!is_numeric($raw) || $raw < 0 || $raw > $maks) {
                $errors[$jawaban->id] = 'Poin harus antara 0 dan ' . (int) $maks . '.';
                continue;
            }
            $catatan = trim((string) ($catatanInput[$jawaban->id] ?? ''));
            if (mb_strlen($catatan) > 500) {
                $errors[$jawaban->id] = 'Catatan maksimal 500 karakter.';
                continue;
            }
            $simpan[] = [$jawaban, round((float) $raw, 2), $catatan ?: null];
        }
        if ($errors || !$simpan) return $errors;

        // Simpan semua nilai dulu, lalu hitung ulang skor (dan badge) sekali saja.
        DB::transaction(function () use ($attempt, $simpan, $penilaiId) {
            foreach ($simpan as [$jawaban, $poin, $catatan]) $this->scoreEssay($jawaban, $poin, $catatan, $penilaiId);
            $this->recalculate($attempt);
        });
        return [];
    }

    /**
     * Soal (array) dengan `opsi` dan `jawaban` peserta. Hanya soal yang punya baris jawaban di percobaan ini
     * (finish() mencatat semua soal saat dikumpulkan), jadi soal yang ditambahkan kemudian tidak ikut tampil.
     */
    public function resultDetail(Attempt $attempt, Quiz $quiz): array
    {
        $jawaban = $this->savedAnswers($attempt);
        $soal = $quiz->questions()->whereIn('id', array_keys($jawaban))->with('answerOptions')->get();
        return $soal->map(fn (Question $s) => $s->attributesToArray() + [
            'opsi' => $s->answerOptions->map(fn ($o) => $o->attributesToArray())->all(),
            'jawaban' => $jawaban[$s->id] ?? null,
        ])->all();
    }

    /** Statistik per soal: `dijawab` dan `benar` dari percobaan yang sudah dikumpulkan. */
    public function questionStats(Quiz $quiz): Collection
    {
        $soal = $quiz->questions()->get(['id', 'urutan', 'teks', 'tipe']);
        $jawaban = UserAnswer::whereIn('question_id', $soal->modelKeys())
            ->whereHas('attempt', fn (Builder $q) => $q->notRunning())
            ->get(['question_id', 'benar'])
            ->groupBy('question_id');

        return $soal->each(function (Question $s) use ($jawaban) {
            $milik = $jawaban->get($s->id, collect());
            $s->setAttribute('dijawab', $milik->count());
            $s->setAttribute('benar', $milik->where('benar', true)->count());
        });
    }

    /** Percobaan yang sudah dikumpulkan pada kuis, 'menunggu' di atas; `user` dimuat. */
    public function forQuizOwner(int $quizId, string $status = ''): Collection
    {
        return Attempt::with('user')
            ->where('quiz_id', $quizId)->notRunning()
            ->when(in_array($status, ['menunggu', 'selesai'], true), fn (Builder $q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'menunggu' THEN 0 ELSE 1 END") // menunggu first; no Eloquent equivalent
            ->orderByDesc('selesai_pada')
            ->get();
    }

    /** @param array{kategori?: string, dari?: string, sampai?: string, nilai?: string} $f */
    public function history(int $userId, array $f, int $limit, int $offset): Collection
    {
        return $this->historyQuery($userId, $f)
            ->with('quiz.category')
            ->orderByDesc('selesai_pada')->orderByDesc('id')
            ->skip($offset)->take($limit)
            ->get();
    }

    public function historyCount(int $userId, array $f): int
    {
        return $this->historyQuery($userId, $f)->count();
    }

    private function historyQuery(int $userId, array $f): Builder
    {
        $tanggal = fn (string $k) => !empty($f[$k]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f[$k]);

        $q = Attempt::where('user_id', $userId)->unarchived()->notRunning();
        if (!empty($f['kategori'])) $q->whereRelation('quiz.category', 'slug', $f['kategori']);
        if ($tanggal('dari')) $q->where('selesai_pada', '>=', $f['dari'] . ' 00:00:00');
        if ($tanggal('sampai')) $q->where('selesai_pada', '<=', $f['sampai'] . ' 23:59:59');
        match ($f['nilai'] ?? '') {
            'tinggi' => $q->where('skor', '>=', 80),
            'sedang' => $q->where('skor', '>=', 60)->where('skor', '<', 80),
            'rendah' => $q->where('skor', '<', 60),
            'menunggu' => $q->where('status', 'menunggu'),
            default => null,
        };
        return $q;
    }

    /** Reset riwayat = pengarsipan, bukan penghapusan. Badge tidak dicabut. */
    public function archiveAll(int $userId): int
    {
        return Attempt::where('user_id', $userId)->unarchived()->notRunning()
            ->update(['diarsipkan' => true, 'diarsipkan_pada' => waktu()]);
    }
}
