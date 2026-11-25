<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Quiz;
use App\Services\AttemptService;
use App\Services\QuestionService;
use App\Services\QuizService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(private QuizService $quizzes) {}

    public function index(Request $request)
    {
        [$f, $kategoriAktif, $topik] = $this->quizzes->exploreFilters($request->query(), (bool) $this->user());
        $pg = paginate($this->quizzes->exploreCount($f), 12);

        return view('jelajah', [
            'title' => 'Jelajah Kuis',
            'menu' => 'jelajah',
            'f' => $f,
            'kategori' => Category::withPublishedCount(),
            'kategoriAktif' => $kategoriAktif,
            'topik' => $topik,
            'hasil' => $this->quizzes->explore($f, $pg['limit'], $pg['offset'], $this->user()),
            'pg' => $pg,
            'aktif' => $this->quizzes->activeFilters($f, $kategoriAktif, $topik),
        ]);
    }

    public function show(int $id, QuestionService $questions, AttemptService $attempts)
    {
        $quiz = $this->visibleQuiz($id);
        $user = $this->user();
        $ringkas = $questions->summary($id);

        $riwayatSaya = collect();
        $berlangsung = null;
        if ($user) {
            $riwayatSaya = $this->quizzes->recentAttempts($quiz, $user);
            $berlangsung = $attempts->ongoing($id, $user->id);
            if ($berlangsung && $attempts->isExpired($berlangsung, $quiz)) $berlangsung = null;
        }

        return view('kuis', [
            'title' => $quiz->judul,
            'menu' => 'jelajah',
            'quiz' => $quiz,
            'tipe' => $ringkas['tipe'],
            'totalPoin' => $ringkas['totalPoin'],
            'peringkat' => $this->quizzes->leaderboard($id),
            'statKuis' => $this->quizzes->stats($id),
            'riwayatSaya' => $riwayatSaya,
            'berlangsung' => $berlangsung,
            'bisaKelola' => $this->quizzes->canManage($quiz, $user),
            'mengajar' => (bool) $user?->hasRole('pengajar', 'admin'),
            'lainnya' => $this->quizzes->related($quiz),
        ]);
    }

    public function start(int $id, AttemptService $attempts)
    {
        $quiz = $this->visibleQuiz($id);
        // Pengajar/admin tidak mengerjakan kuis agar statistik peserta tetap bersih.
        if ($this->user()->hasRole('pengajar', 'admin')) {
            flash('info', 'Pengajar dan administrator tidak mengerjakan kuis. Gunakan Kelola soal untuk memeriksa isi kuis.');
            return redirect()->route('kuis.show', $id);
        }
        if ($quiz->status !== 'terbit') {
            flash('error', 'Kuis masih draft. Terbitkan terlebih dahulu agar dapat dikerjakan.');
            return redirect()->route('kuis.show', $id);
        }
        if ((int) $quiz->jumlah_soal === 0) {
            flash('error', 'Kuis ini belum memiliki soal.');
            return redirect()->route('kuis.show', $id);
        }
        return redirect()->route('kerjakan', $attempts->start($quiz, $this->user()->id)->id);
    }

    private function visibleQuiz(int $id): Quiz
    {
        $quiz = $this->quizzes->find($id);
        abort_unless($quiz && ($quiz->status === 'terbit' || $this->quizzes->canManage($quiz, $this->user())), 404);
        return $quiz;
    }
}
