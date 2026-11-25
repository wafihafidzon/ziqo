<?php

namespace App\Http\Controllers\Pengajar;

use App\Http\Controllers\Controller;
use App\Services\QuestionService;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function __construct(private QuestionService $questions) {}

    public function index(int $kuis)
    {
        $quiz = $this->kuisKelola($kuis);
        return view('pengajar.soal', [
            'title' => 'Soal: ' . $quiz->judul,
            'menu' => 'kuis-saya',
            'quiz' => $quiz,
            'soal' => $this->questions->forQuiz($kuis),
            'adaPercobaan' => (int) $quiz->jumlah_percobaan > 0,
        ]);
    }

    public function action(Request $request, int $kuis)
    {
        $quiz = $this->kuisKelola($kuis);
        $soal = $this->questions->find($request->integer('soal'), $kuis);
        abort_unless($soal, 404);
        $aksi = $request->input('aksi');
        if ($aksi === 'naik' || $aksi === 'turun') {
            $this->questions->move($quiz, $soal, $aksi);
        } elseif ($aksi === 'hapus') {
            if ($this->questions->delete($quiz, $soal)) {
                flash('sukses', 'Soal dihapus.');
            } else {
                flash('error', 'Kuis terbit harus memiliki minimal satu soal. Jadikan draft terlebih dahulu.');
            }
        }
        return redirect()->route('pengajar.soal', $kuis);
    }

    public function form(Request $request, int $kuis, ?int $id = null)
    {
        $quiz = $this->kuisKelola($kuis);
        $soal = null;
        if ($id) {
            $soal = $this->questions->find($id, $kuis);
            abort_unless($soal, 404);
        }
        $tipe = $soal?->tipe ?? (string) $request->input('tipe');
        if (!isset(TIPE_SOAL[$tipe])) $tipe = 'pg';

        if ($request->isMethod('post')) {
            [$data, $opsi, $errors] = $this->questions->validate(['tipe' => $tipe] + $request->all());
            if ($errors) return $this->gagal($errors);
            $this->questions->save($quiz, $soal, $data, $opsi);
            flash('sukses', $soal ? 'Soal diperbarui.' : 'Soal ditambahkan.');
            if (!$soal && $request->input('lagi') === '1') return redirect()->route('pengajar.soal.create', [$kuis, 'tipe' => $tipe]);
            return redirect()->route('pengajar.soal', $kuis);
        }

        return view('pengajar.soal-form', [
            'title' => ($soal ? 'Edit' : 'Tambah') . ' soal',
            'menu' => 'kuis-saya',
            'quiz' => $quiz,
            'soal' => $soal,
            'tipe' => $tipe,
            'v' => $this->questions->formValues($soal, $tipe, $request->old() ?: []),
            'errors' => form_errors(),
            'scripts' => ['assets/js/editor-soal.js'],
        ]);
    }
}
