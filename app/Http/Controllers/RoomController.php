<?php

namespace App\Http\Controllers;

use App\Services\AttemptService;
use App\Services\QuizService;
use App\Services\RoomService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(private RoomService $rooms) {}

    public function show(Request $request)
    {
        if ($this->bukanPeserta()) {
            flash('info', 'Masuk room hanya untuk peserta. Pantau room dan pesertanya di halaman ini.');
            return redirect()->route('pengajar.rooms');
        }

        return view('room', [
            'title' => 'Masuk Room',
            'menu' => 'room',
            'kode' => $request->old('kode', $this->rooms->normalize($request->query('kode'))),
            'errors' => form_errors(),
        ]);
    }

    public function join(Request $request, QuizService $quizzes, AttemptService $attempts)
    {
        abort_if($this->bukanPeserta(), 403, 'Pengajar dan administrator tidak ikut mengerjakan kuis di room.');

        $room = $this->rooms->findByCode($this->rooms->normalize($request->input('kode')));
        if (!$room) return $this->gagal(['kode' => 'Kode room tidak ditemukan. Periksa lagi kode dari pengajarmu.']);
        if ($room->status !== 'buka') return $this->gagal(['kode' => 'Room ini sudah ditutup oleh pengajar.']);

        $quiz = $quizzes->find($room->quiz_id);
        if (!$quiz || $quiz->status !== 'terbit' || (int) $quiz->jumlah_soal === 0) {
            return $this->gagal(['kode' => 'Kuis di room ini belum bisa dikerjakan. Hubungi pengajarmu.']);
        }

        return redirect()->route('kerjakan', $attempts->start($quiz, $this->user()->id, $room->id)->id);
    }

    /** Room dipakai peserta untuk mengerjakan; pengajar/admin hanya memantau. */
    private function bukanPeserta(): bool
    {
        return $this->user()->hasRole('pengajar', 'admin');
    }
}
