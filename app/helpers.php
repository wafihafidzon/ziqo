<?php

use App\Support\Avatar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;

const KESULITAN = ['mudah' => 'Mudah', 'menengah' => 'Menengah', 'sulit' => 'Sulit'];
const JENJANG = ['sd' => 'SD', 'smp' => 'SMP', 'sma' => 'SMA/SMK', 'pt' => 'Perguruan Tinggi', 'umum' => 'Umum'];
const TIPE_SOAL = [
    'pg' => 'Pilihan Ganda',
    'pgk' => 'Pilihan Ganda Kompleks',
    'bs' => 'Benar / Salah',
    'isian' => 'Isian Singkat',
    'esai' => 'Esai',
];
const ROLE = ['peserta' => 'Peserta', 'pengajar' => 'Pengajar', 'admin' => 'Administrator'];
const WARNA = ['ungu', 'kuning', 'hijau', 'biru', 'pink', 'oranye', 'merah'];
const STATUS_ATTEMPT = ['berlangsung' => 'Berlangsung', 'menunggu' => 'Menunggu penilaian', 'selesai' => 'Selesai'];

function waktu(): string
{
    return now()->format('Y-m-d H:i:s');
}

function flash(string $tipe, string $pesan): void
{
    session()->push('toasts', ['tipe' => $tipe, 'pesan' => $pesan]);
}

function validasi(array $data, array $rules, array $label = []): array
{
    return array_map(fn ($m) => $m[0], Validator::make($data, $rules, [], $label)->errors()->toArray());
}

function form_errors(): array
{
    $bag = session('errors');
    return $bag instanceof ViewErrorBag ? array_map(fn ($m) => $m[0], $bag->getBag('default')->toArray()) : [];
}

function label(array $map, ?string $key): string
{
    return $map[$key] ?? '-';
}

function tanggal(?string $dt, bool $jam = false): string
{
    if (!$dt) return '-';
    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($dt);
    $s = date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
    return $jam ? $s . ', ' . date('H.i', $t) : $s;
}

function durasi($detik): string
{
    if ($detik === null) return '-';
    $detik = (int) $detik;
    $m = intdiv($detik, 60);
    $s = $detik % 60;
    if ($m >= 60) return intdiv($m, 60) . ' j ' . ($m % 60) . ' m';
    return $m > 0 ? "$m m $s d" : "$s d";
}

function angka($n, int $desimal = 0): string
{
    return number_format((float) $n, $desimal, ',', '.');
}

function salam(): string
{
    $j = (int) date('G');
    return $j < 11 ? 'Selamat pagi' : ($j < 15 ? 'Selamat siang' : ($j < 18 ? 'Selamat sore' : 'Selamat malam'));
}

/** Induk halaman kelola kuis: admin kembali ke Pantau Kuis, pengajar ke Kuis Saya. */
function kuis_induk(): array
{
    return auth()->user()?->hasRole('admin') ? ['Pantau kuis', route('admin.kuis')] : ['Kuis saya', route('pengajar.kuis')];
}

function avatar_url($user): string
{
    $nama = $user['avatar'] ?? null;
    return Avatar::exists($nama) ? Avatar::url($nama) : asset('assets/maskot.webp');
}

function inisial(string $nama): string
{
    $kata = preg_split('/\s+/', trim($nama)) ?: [''];
    return mb_strtoupper(mb_substr($kata[0], 0, 1) . (isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
}

function estimasi_menit(array|ArrayAccess $quiz): int
{
    if ((int) $quiz['durasi_menit'] > 0) return (int) $quiz['durasi_menit'];
    $n = (int) ($quiz['jumlah_soal'] ?? 0);
    $esai = (int) ($quiz['jumlah_esai'] ?? 0);
    return max(1, $n - $esai + $esai * 3);
}

function icon(string $nama, string $class = ''): string
{
    static $p = [
        'home'      => '<path d="M3 11 12 3l9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
        'jelajah'   => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
        'quiz'      => '<rect x="4" y="3" width="16" height="18" rx="3"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'kategori'  => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="8" cy="8" r="1.5"/>',
        'pengguna'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.5-3.5 3.2-5.5 6.5-5.5s6 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18.5 14.8c1.8.7 2.8 2.4 3 5.2"/>',
        'profil'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.8-4 4-6 8-6s7.2 2 8 6"/>',
        'logout'    => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
        'login'     => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 16l4-4-4-4M14 12H4"/>',
        'cari'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'filter'    => '<path d="M4 5h16l-6 7.5V19l-4 2v-8.5z"/>',
        'panah-kanan' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'panah-kiri'  => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'chevron'   => '<path d="m9 6 6 6-6 6"/>',
        'chevron-bawah' => '<path d="m6 9 6 6 6-6"/>',
        'kamera'    => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
        'mata'      => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'kunci'     => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.8-4 4-6 8-6s7.2 2 8 6"/>',
        'jam'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'soal'      => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6"/><path d="M12 17h.01"/>',
        'cek'       => '<path d="m5 12 5 5 9-10"/>',
        'silang'    => '<path d="M6 6l12 12M18 6 6 18"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'edit'      => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'sampah'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'riwayat'   => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
        'reset'     => '<path d="M20 12a8 8 0 1 1-2.3-5.7L20 8"/><path d="M20 3v5h-5"/>',
        'chart'     => '<path d="M4 20V4M4 20h16"/><path d="m7 15 4-5 3 3 5-7"/>',
        'trofi'     => '<path d="M8 4h8v5a4 4 0 0 1-8 0z"/><path d="M8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4M12 13v4M8 21h8M9 17h6"/>',
        'bintang'   => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
        'langkah'   => '<path d="M7 17c-2 0-3-1.5-3-3.5S5.5 9 7 9s2.5 2 2.5 4S9 17 7 17zM17 13c-1.5 0-2.5-1.5-2.5-3.5S15.5 5 17 5s3 2 3 4-1.5 4-3 4z"/><path d="M6 20h2M16 16h2"/>',
        'api'       => '<path d="M12 3c1 4 5 5.5 5 10a5 5 0 0 1-10 0c0-2 1-3.5 2-4.5 0 2 1 3 2 3 0-3-1-5 1-8.5z"/>',
        'mahkota'   => '<path d="m3 8 4.5 4L12 5l4.5 7L21 8l-2 11H5z"/>',
        'kompas'    => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
        'kode'      => '<path d="m8 8-4 4 4 4M16 8l4 4-4 4M13.5 5l-3 14"/>',
        'buku'      => '<path d="M4 5a2 2 0 0 1 2-2h6v16H6a2 2 0 0 0-2 2zM20 5a2 2 0 0 0-2-2h-6v16h6a2 2 0 0 1 2 2z"/>',
        'medali'    => '<circle cx="12" cy="15" r="5"/><path d="M8.5 11.5 6 3h4l2 5 2-5h4l-2.5 8.5"/>',
        'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/>',
        'peringatan'=> '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17h.01"/>',
        'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'kirim'     => '<path d="M4 12 20 4l-6 16-3-7z"/>',
        'pena'      => '<path d="M4 20h4L19 9l-4-4L4 16z"/>',
        'perisai'   => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'kilat'     => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'target'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'arsip'     => '<rect x="3" y="4" width="18" height="5" rx="1"/><path d="M5 9v10h14V9M10 13h4"/>',
        'unggah'    => '<path d="M12 16V4M7 9l5-5 5 5M4 16v4h16v-4"/>',
        'power'     => '<path d="M12 3v9"/><path d="M6.3 7a8 8 0 1 0 11.4 0"/>',
        'google'    => '',
    ];
    if (empty($p[$nama])) return '';
    return '<svg class="ikon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[$nama] . '</svg>';
}

/** @return array{limit: int, offset: int, hal: int, maks: int, total: int} */
function paginate(int $total, int $perHalaman = 12): array
{
    $maks = max(1, (int) ceil($total / $perHalaman));
    $hal = min(max(1, request()->integer('hal', 1)), $maks);
    return ['limit' => $perHalaman, 'offset' => ($hal - 1) * $perHalaman, 'hal' => $hal, 'maks' => $maks, 'total' => $total];
}

function err(array $errors, $key): string
{
    return isset($errors[$key]) ? '<div class="pesan-salah" id="err-' . e($key) . '">' . icon('peringatan') . e($errors[$key]) . '</div>' : '';
}

function aria_err(array $errors, $key): string
{
    return isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
}

function warna(?string $w): string
{
    return 'w-' . (in_array($w, WARNA, true) ? $w : 'ungu');
}

function warna_skor($skor): string
{
    if ($skor === null) return 'w-kuning';
    return $skor >= 80 ? 'w-hijau' : ($skor >= 60 ? 'w-biru' : 'w-merah');
}

function kueri(array $q): array
{
    return array_filter($q, fn ($v) => $v !== null && $v !== '' && $v !== []);
}
