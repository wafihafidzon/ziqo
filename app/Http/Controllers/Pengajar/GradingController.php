<?php

namespace App\Http\Controllers\Pengajar;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Services\AttemptService;
use App\Services\QuizService;
use App\Services\StatsService;
use Illuminate\Http\Request;

class GradingController extends Controller
{
    public function __construct(private AttemptService $attempts)
    {
    }

    public function results(Request $request, QuizService $quizzes, int $kuis)
    {
        $quiz = $this->kuisKelola($kuis);
        $status = in_array($request->input('status'), ['menunggu', 'selesai'], true) ? $request->input('status') : '';

        return view('pengajar.hasil', [
            'title' => 'Hasil: ' . $quiz['judul'],
            'menu' => 'kuis-saya',
            'quiz' => $quiz,
            'status' => $status,
            'rows' => $this->attempts->forQuizOwner($kuis, $status),
            'stat' => $quizzes->stats($kuis),
            'perSoal' => $this->attempts->questionStats($quiz),
        ]);
    }

    public function queue(StatsService $stats)
    {
        $user = $this->user();
        return view('pengajar.penilaian', [
            'title' => 'Penilaian Esai',
            'menu' => 'penilaian',
            'rows' => $stats->pendingEssays($user->hasRole('admin') ? null : $user->id, 100),
        ]);
    }

    public function show(int $id)
    {
        [$attempt, $quiz] = $this->load($id);
        $peserta = $attempt->user;
        return view('pengajar.nilai', [
            'title' => 'Penilaian: ' . $peserta['nama'],
            'menu' => 'penilaian',
            'quiz' => $quiz,
            'a' => $attempt,
            'peserta' => $peserta,
            'detail' => $this->attempts->resultDetail($attempt, $quiz),
            'errors' => form_errors(),
        ]);
    }

    public function grade(Request $request, int $id)
    {
        [$attempt] = $this->load($id);
        $errors = $this->attempts->gradeEssays(
            $attempt, (array) $request->input('poin', []), (array) $request->input('catatan', []), $this->user()->id);
        if ($errors) return $this->gagal($errors);

        $attempt->refresh();
        flash('sukses', $attempt->status === 'selesai'
            ? 'Penilaian disimpan. Nilai akhir ' . $attempt->user['nama'] . ': ' . angka($attempt->skor) . '.'
            : 'Penilaian disimpan. Masih ada esai yang belum dinilai.');
        return redirect()->route('pengajar.nilai', $id);
    }

    private function load(int $id): array
    {
        $attempt = Attempt::find($id);
        abort_if(!$attempt || $attempt->status === 'berlangsung', 404);
        return [$attempt, $this->kuisKelola($attempt->quiz_id)];
    }
}
