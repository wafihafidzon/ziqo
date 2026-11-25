<?php

namespace App\Http\Controllers\Pengajar;

use App\Http\Controllers\Controller;
use App\Services\RoomService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(private RoomService $rooms) {}

    public function index(Request $request, int $kuis)
    {
        $quiz = $this->kuisKelola($kuis);
        $rooms = $this->rooms->forQuiz($kuis);
        $pilih = $request->integer('room') ? $rooms->firstWhere('id', $request->integer('room')) : null;

        return view('pengajar.room', [
            'title' => 'Room: ' . $quiz['judul'],
            'menu' => 'kuis-saya',
            'quiz' => $quiz,
            'rooms' => $rooms,
            'pilih' => $pilih,
            'peserta' => $pilih ? $this->rooms->participants($pilih) : collect(),
        ]);
    }

    public function action(Request $request, int $kuis)
    {
        $quiz = $this->kuisKelola($kuis);
        $aksi = $request->input('aksi');

        if ($aksi === 'buat') {
            if ($quiz->status !== 'terbit') {
                flash('error', 'Terbitkan kuis terlebih dahulu sebelum membuat room.');
                return redirect()->route('pengajar.room', $kuis);
            }
            $room = $this->rooms->create($quiz, $this->user());
            flash('sukses', 'Room dibuat dengan kode ' . $room->kode_akses . '. Bagikan kode ini ke peserta.');
            return redirect()->route('pengajar.room', [$kuis, 'room' => $room->id]);
        }

        $room = $this->rooms->find($kuis, $request->integer('id'));
        abort_unless($room, 404);

        switch ($aksi) {
            case 'tutup':
            case 'buka':
                $this->rooms->setStatus($room, $aksi);
                flash('sukses', 'Room ' . $room->kode_akses . ($aksi === 'buka' ? ' dibuka kembali.' : ' ditutup. Peserta baru tidak bisa masuk.'));
                break;
            case 'hapus':
                if ($this->rooms->delete($room)) {
                    flash('sukses', 'Room ' . $room->kode_akses . ' dihapus.');
                    return redirect()->route('pengajar.room', $kuis);
                }
                flash('error', 'Room sudah memiliki peserta sehingga tidak dapat dihapus. Tutup room untuk menghentikan akses.');
                break;
        }
        return redirect()->route('pengajar.room', [$kuis, 'room' => $room->id]);
    }
}
