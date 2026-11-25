<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\AttemptService;
use App\Services\BadgeService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(private AttemptService $attempts, private BadgeService $badges)
    {
    }

    public function history(Request $request)
    {
        $uid = $this->user()->id;
        $nilai = (string) $request->input('nilai');
        $f = [
            'kategori' => (string) $request->input('kategori'),
            'dari' => (string) $request->input('dari'),
            'sampai' => (string) $request->input('sampai'),
            'nilai' => in_array($nilai, ['tinggi', 'sedang', 'rendah', 'menunggu'], true) ? $nilai : '',
        ];
        $pg = paginate($this->attempts->historyCount($uid, $f), 10);
        return view('riwayat', [
            'title' => 'Riwayat Pengerjaan',
            'menu' => 'riwayat',
            'f' => $f,
            'rows' => $this->attempts->history($uid, $f, $pg['limit'], $pg['offset']),
            'pg' => $pg,
            'kategori' => Category::urutNama()->get(),
            'adaRiwayat' => $this->attempts->historyCount($uid, []) > 0,
        ]);
    }

    public function resetHistory(Request $request)
    {
        if ($request->input('aksi') === 'reset') {
            if ($request->input('konfirmasi_teks') !== 'RESET') {
                flash('error', 'Ketik RESET untuk mengonfirmasi.');
            } else {
                $n = $this->attempts->archiveAll($this->user()->id);
                flash('sukses', $n ? "$n riwayat diarsipkan. Statistik pribadimu dimulai dari nol; badge tetap tersimpan." : 'Tidak ada riwayat untuk direset.');
            }
        }
        return redirect()->route('riwayat');
    }

    public function achievements()
    {
        $koleksi = $this->badges->collection($this->user()->id);
        return view('pencapaian', [
            'title' => 'Pencapaian',
            'menu' => 'pencapaian',
            'koleksi' => $koleksi,
            'dimiliki' => $koleksi->filter(fn ($b) => $b['diperoleh_pada'])->count(),
        ]);
    }
}
