<?php

namespace App\Http\Controllers\Pengajar;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\QuizService;
use App\Services\StatsService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(private QuizService $quizzes) {}

    public function index(StatsService $stats)
    {
        return view('pengajar.kuis', [
            'title' => 'Kuis Saya',
            'menu' => 'kuis-saya',
            'kuis' => $this->quizzes->forOwner($this->user()->id),
            'stat' => $stats->teacher($this->user()->id),
        ]);
    }

    public function action(Request $request)
    {
        $quiz = $this->kuisKelola($request->integer('id'));
        switch ($request->input('aksi')) {
            case 'terbit':
                if ($this->quizzes->publish($quiz)) {
                    flash('sukses', 'Kuis "' . $quiz->judul . '" diterbitkan dan kini dapat dikerjakan peserta.');
                } else {
                    flash('error', 'Tambahkan minimal satu soal sebelum menerbitkan kuis.');
                }
                break;
            case 'draft':
                $this->quizzes->unpublish($quiz);
                flash('sukses', 'Kuis dikembalikan ke draft dan disembunyikan dari peserta.');
                break;
            case 'hapus':
                if ($this->quizzes->delete($quiz)) {
                    flash('sukses', 'Kuis "' . $quiz->judul . '" dihapus.');
                } else {
                    flash('error', 'Kuis sudah pernah dikerjakan sehingga tidak dapat dihapus. Jadikan draft untuk menyembunyikannya.');
                }
                break;
        }
        return redirect()->route($request->input('kembali') === 'admin' ? 'admin.kuis' : 'pengajar.kuis');
    }

    public function form(Request $request, ?int $id = null)
    {
        $quiz = $id ? $this->kuisKelola($id) : null;

        if ($request->isMethod('post')) {
            [$data, $errors] = $this->quizzes->validate($request->all());
            if ($errors) return $this->gagal($errors);
            if ($quiz) {
                $this->quizzes->update($quiz, $data);
                flash('sukses', 'Pengaturan kuis disimpan.');
                return redirect()->route('pengajar.soal', $quiz->id);
            }
            $baru = $this->quizzes->create($data, $this->user());
            flash('sukses', 'Kuis dibuat sebagai draft. Sekarang tambahkan soal.');
            return redirect()->route('pengajar.soal', $baru->id);
        }

        $bawaan = ['kesulitan' => 'mudah', 'jenjang' => 'umum', 'durasi_menit' => 0, 'tampilkan_feedback' => 1, 'navigasi_bebas' => 1, 'akses' => 'publik'];
        return view('pengajar.kuis-form', [
            'title' => $quiz ? 'Edit Kuis' : 'Buat Kuis',
            'menu' => 'kuis-saya',
            'quiz' => $quiz,
            'v' => $request->old() ?: ($quiz?->attributesToArray() ?? $bawaan),
            'errors' => form_errors(),
            'kategori' => Category::urutNama()->get(),
            'subMap' => Category::topicMap(),
        ]);
    }
}
