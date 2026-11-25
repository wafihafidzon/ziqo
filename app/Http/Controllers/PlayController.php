<?php

namespace App\Http\Controllers;

use App\Services\AttemptService;
use App\Services\BadgeService;
use App\Services\QuizService;
use Illuminate\Http\Request;

class PlayController extends Controller
{
    public function __construct(private AttemptService $attempts, private QuizService $quizzes, private BadgeService $badges)
    {
    }

    public function show(int $id)
    {
        [$attempt, $quiz] = $this->load($id, true);
        if ($attempt->status !== 'berlangsung') return redirect()->route('hasil', $id);
        if ($this->attempts->isExpired($attempt, $quiz)) {
            $this->attempts->finish($attempt);
            flash('info', 'Waktu pengerjaan habis. Jawaban yang tersimpan sudah dikumpulkan.');
            return redirect()->route('hasil', $id);
        }

        return view('main', [
            'title' => 'Mengerjakan: ' . $quiz['judul'],
            'quiz' => $quiz,
            'attempt' => $attempt,
            'config' => [
                'endpoint' => route('kerjakan', $id),
                'csrf' => csrf_token(),
                ...$this->attempts->playConfig($attempt, $quiz),
                'tipeLabel' => TIPE_SOAL,
            ],
            'scripts' => ['assets/js/main.js'],
        ]);
    }

    public function handle(Request $request, int $id)
    {
        [$attempt, $quiz] = $this->load($id, true);
        if ($attempt->status !== 'berlangsung') {
            return response()->json(['ok' => false, 'pesan' => 'Percobaan sudah selesai.', 'redirect' => route('hasil', $id)], 409);
        }
        if ($this->attempts->isExpired($attempt, $quiz)) {
            $this->attempts->finish($attempt);
            return response()->json(['ok' => false, 'pesan' => 'Waktu habis. Jawaban dikumpulkan otomatis.', 'redirect' => route('hasil', $id)], 409);
        }

        $aksi = (string) $request->input('aksi');
        if ($aksi === 'selesai') {
            $this->attempts->finish($attempt);
            return response()->json(['ok' => true, 'redirect' => route('hasil', [$id, 'baru' => 1])]);
        }

        $soal = $this->attempts->question($quiz, $request->integer('soal'));
        if (!$soal) return response()->json(['ok' => false, 'pesan' => 'Soal tidak ditemukan.'], 404);

        $nilai = $request->input('nilai');
        if (is_array($nilai)) $nilai = array_values(array_filter($nilai, 'is_scalar'));
        elseif (!is_string($nilai)) $nilai = in_array($soal->tipe, ['isian', 'esai'], true) ? '' : [];
        if (in_array($soal->tipe, ['pg', 'bs', 'pgk'], true) && !is_array($nilai)) $nilai = $nilai === '' ? [] : [$nilai];

        if (!(int) $quiz['navigasi_bebas']) $this->attempts->lockBefore($attempt, $quiz, $soal->id);

        if ($aksi === 'cek') {
            $hasil = $this->attempts->check($attempt, $quiz, $soal, $nilai);
            return response()->json($hasil, $hasil['ok'] ? 200 : 422);
        }
        $err = $this->attempts->saveAnswer($attempt, $quiz, $soal, $nilai, $request->input('kunci') === '1');
        return response()->json($err ? ['ok' => false, 'pesan' => $err] : ['ok' => true], $err ? 422 : 200);
    }

    public function result(Request $request, int $id)
    {
        [$attempt, $quiz] = $this->load($id);
        if ($attempt->status === 'berlangsung') return redirect()->route('kerjakan', $id);
        return view('hasil', [
            'title' => 'Hasil: ' . $quiz['judul'],
            'menu' => 'riwayat',
            'quiz' => $quiz,
            'a' => $attempt,
            'detail' => $this->attempts->resultDetail($attempt, $quiz),
            'badgeBaru' => $this->badges->earnedOn($attempt),
            'baru' => $request->input('baru') === '1',
        ]);
    }

    /** $main: percobaan yang masih berjalan hanya bisa dikerjakan selama kuis terbit; hasil percobaan selesai tetap terbuka. */
    private function load(int $id, bool $main = false): array
    {
        $attempt = $this->attempts->ownedBy($id, $this->user()->id);
        abort_unless($attempt, 404);
        $quiz = $this->quizzes->find($attempt->quiz_id);
        if ($main && $attempt->status === 'berlangsung') {
            abort_unless($quiz->status === 'terbit' || $this->quizzes->canManage($quiz, $this->user()), 404);
        }
        return [$attempt, $quiz];
    }
}
