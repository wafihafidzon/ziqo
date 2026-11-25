<?php

namespace App\Services;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    /** Soal kuis berurutan beserta relasi `opsi`. */
    public function forQuiz(int $quizId): Collection
    {
        return Question::where('quiz_id', $quizId)->urut()->with('opsi')->get();
    }

    /** Soal untuk halaman pengerjaan: tanpa kunci, pembahasan, atau opsi untuk isian/esai. */
    public function forPlay(int $quizId): array
    {
        return $this->forQuiz($quizId)->map(fn (Question $s) => [
            'id' => $s->id,
            'tipe' => $s->tipe,
            'teks' => $s->teks,
            'poin' => $s->poin,
            'opsi' => in_array($s->tipe, ['pg', 'pgk', 'bs'], true)
                ? $s->opsi->map(fn (AnswerOption $o) => ['id' => $o->id, 'teks' => $o->teks])->all()
                : [],
        ])->all();
    }

    public function find(int $questionId, int $quizId): ?Question
    {
        return Question::where('quiz_id', $quizId)->find($questionId);
    }

    /** @return array{tipe: array<string, int>, totalPoin: int} jumlah soal per jenis dan total poin */
    public function summary(int $quizId): array
    {
        $soal = Question::where('quiz_id', $quizId)->urut()->get(['tipe', 'poin']);
        return ['tipe' => $soal->countBy('tipe')->all(), 'totalPoin' => (int) $soal->sum('poin')];
    }

    /** @return array{0: array, 1: array, 2: array} [data soal, opsi [teks, benar], errors] */
    public function validate(array $in): array
    {
        $tipe = $in['tipe'] ?? '';
        $errors = validasi($in, [
            'tipe' => 'required|in:' . implode(',', array_keys(TIPE_SOAL)),
            'teks' => 'required|min:5|max:2000',
            'poin' => 'required|integer|between:1,100',
            'pembahasan' => 'nullable|max:2000',
        ], ['tipe' => 'Jenis soal', 'teks' => 'Pertanyaan', 'poin' => 'Poin', 'pembahasan' => 'Pembahasan']);
        $add = function (string $k, string $m) use (&$errors) { $errors[$k] ??= $m; };

        $opsi = [];
        if ($tipe === 'pg' || $tipe === 'pgk') {
            $raw = array_map(fn ($t) => trim((string) $t), (array) ($in['opsi'] ?? []));
            $teks = array_values(array_filter($raw, fn ($t) => $t !== ''));
            $kunci = $tipe === 'pg' ? [(string) ($in['kunci'] ?? '')] : array_map('strval', (array) ($in['kunci'] ?? []));
            foreach ($raw as $i => $t) {
                if ($t === '') continue;
                if (mb_strlen($t) > 255) $add('opsi', 'Teks opsi maksimal 255 karakter.');
                $opsi[] = [$t, in_array((string) $i, $kunci, true) ? 1 : 0];
            }
            $jumlahBenar = array_sum(array_column($opsi, 1));
            if (count($teks) < 2) $add('opsi', 'Isi minimal 2 opsi jawaban.');
            elseif (count($teks) > 6) $add('opsi', 'Maksimal 6 opsi jawaban.');
            elseif (count(array_unique(array_map('mb_strtolower', $teks))) !== count($teks)) $add('opsi', 'Opsi jawaban tidak boleh sama.');
            elseif ($tipe === 'pg' && $jumlahBenar !== 1) $add('kunci', 'Pilih tepat satu kunci jawaban.');
            elseif ($tipe === 'pgk' && $jumlahBenar < 1) $add('kunci', 'Tandai minimal satu jawaban benar.');
            elseif ($tipe === 'pgk' && $jumlahBenar === count($opsi)) $add('kunci', 'Tidak semua opsi boleh benar.');
        } elseif ($tipe === 'bs') {
            $k = $in['kunci_bs'] ?? '';
            if (!in_array($k, ['benar', 'salah'], true)) $add('kunci_bs', 'Pilih kunci jawaban Benar atau Salah.');
            $opsi = [['Benar', $k === 'benar' ? 1 : 0], ['Salah', $k === 'salah' ? 1 : 0]];
        } elseif ($tipe === 'isian') {
            foreach ((array) ($in['jawaban'] ?? []) as $j) {
                $j = trim((string) $j);
                if ($j === '') continue;
                if (mb_strlen($j) > 255) $add('jawaban', 'Jawaban maksimal 255 karakter.');
                $opsi[] = [$j, 1];
            }
            if (!$opsi) $add('jawaban', 'Isi minimal satu jawaban yang diterima.');
            if (count($opsi) > 8) $add('jawaban', 'Maksimal 8 variasi jawaban.');
        }

        $data = [
            'tipe' => $tipe,
            'teks' => trim($in['teks'] ?? ''),
            'poin' => (int) ($in['poin'] ?? 10),
            'pembahasan' => trim($in['pembahasan'] ?? '') ?: null,
            'pencocokan' => ($in['pencocokan'] ?? '') === 'ketat' ? 'ketat' : 'longgar',
        ];
        return [$data, $opsi, $errors];
    }

    /**
     * Opsi diperbarui per posisi agar id opsi lama tetap sama dan riwayat jawaban tidak rusak.
     * Jenis soal yang berubah menghapus semua opsi lama.
     */
    public function save(Quiz $quiz, ?Question $soal, array $data, array $opsi): Question
    {
        return DB::transaction(function () use ($quiz, $soal, $data, $opsi) {
            if ($soal) {
                if ($soal->tipe !== $data['tipe']) AnswerOption::where('question_id', $soal->id)->delete();
                $soal->update($data);
            } else {
                $urutan = (int) Question::where('quiz_id', $quiz->id)->max('urutan') + 1;
                $soal = $quiz->questions()->create($data + ['urutan' => $urutan]);
            }
            $ada = $soal->opsi()->get();
            foreach ($opsi as $i => [$teks, $benar]) {
                $isi = ['teks' => $teks, 'benar' => $benar, 'urutan' => $i + 1];
                isset($ada[$i]) ? $ada[$i]->update($isi) : $soal->opsi()->create($isi);
            }
            AnswerOption::whereKey($ada->slice(count($opsi))->modelKeys())->delete();
            $quiz->touch();
            return $soal;
        });
    }

    /** Tukar posisi soal dengan tetangganya, lalu nomori ulang 1..n. */
    public function move(Quiz $quiz, Question $soal, string $arah): void
    {
        $ids = Question::where('quiz_id', $quiz->id)->urut()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $i = array_search((int) $soal->id, $ids, true);
        if ($i === false) return;
        $j = $arah === 'naik' ? $i - 1 : $i + 1;
        if (!isset($ids[$j])) return;
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        DB::transaction(function () use ($ids) {
            foreach ($ids as $n => $id) Question::whereKey($id)->update(['urutan' => $n + 1]);
        });
    }

    /** Kuis terbit harus tetap punya minimal satu soal. */
    public function delete(Quiz $quiz, Question $soal): bool
    {
        if ($quiz->status === 'terbit' && Question::where('quiz_id', $quiz->id)->count() === 1) return false;
        $soal->delete();
        return true;
    }

    /** Nilai awal form soal: input lama, isi soal yang diedit, atau bawaan soal baru. */
    public function formValues(?Question $soal, string $tipe, array $old): array
    {
        if ($old) return $old;
        if (!$soal) {
            return ['poin' => $tipe === 'esai' ? 20 : 10, 'opsi' => ['', '', '', ''], 'jawaban' => [''], 'pencocokan' => 'longgar'];
        }
        $opsi = $soal->opsi->values();
        $teks = $opsi->pluck('teks')->all();
        $benarIdx = $opsi->filter(fn (AnswerOption $o) => $o->benar)->keys()->all();
        return $soal->attributesToArray() + [
            'opsi' => $teks,
            'jawaban' => $teks,
            'kunci' => $tipe === 'pg' ? (string) ($benarIdx[0] ?? '') : array_map('strval', $benarIdx),
            'kunci_bs' => $tipe === 'bs' ? ($opsi->first()?->benar ? 'benar' : 'salah') : '',
        ];
    }
}
