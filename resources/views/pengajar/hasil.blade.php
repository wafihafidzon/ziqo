@extends('layouts.app')

@section('konten')
<div class="remah"><a href="{{ route('pengajar.kuis') }}">Kuis saya</a> {!! icon('chevron') !!} <span>{{ $quiz['judul'] }}</span></div>
<div class="kepala-halaman">
  <div><h1>Hasil peserta</h1><p>{{ $quiz['judul'] }} · {{ $quiz['kategori'] }}</p></div>
  <a class="btn btn-putih" href="{{ route('pengajar.soal', $quiz['id']) }}">{!! icon('soal') !!} Kelola soal</a>
</div>

<div class="grid grid-4 mb-1">
  <div class="stat w-ungu"><span class="stat-ikon">{!! icon('riwayat') !!}</span><span class="stat-nilai">{!! (int) ($stat['percobaan'] ?? 0) !!}</span><span class="stat-label">Pengerjaan</span></div>
  <div class="stat w-biru"><span class="stat-ikon">{!! icon('pengguna') !!}</span><span class="stat-nilai">{!! (int) ($stat['peserta'] ?? 0) !!}</span><span class="stat-label">Peserta unik</span></div>
  <div class="stat w-hijau"><span class="stat-ikon">{!! icon('target') !!}</span><span class="stat-nilai">{!! isset($stat['rata']) && $stat['rata'] !== null ? angka($stat['rata'], 1) : '–' !!}</span><span class="stat-label">Rata-rata nilai</span></div>
  <div class="stat w-kuning"><span class="stat-ikon">{!! icon('pena') !!}</span><span class="stat-nilai">{!! (int) ($stat['menunggu'] ?? 0) !!}</span><span class="stat-label">Menunggu penilaian</span></div>
</div>

<div class="grid grid-sidebar">
  <section class="kartu">
    <div class="kartu-judul">
      <h2>{!! icon('pengguna') !!} Daftar pengerjaan</h2>
      <nav class="tabs mb-0">
        <a href="?kuis={!! (int) $quiz['id'] !!}" class="{!! $status === '' ? 'aktif' : '' !!}">Semua</a>
        <a href="?kuis={!! (int) $quiz['id'] !!}&status=menunggu" class="{!! $status === 'menunggu' ? 'aktif' : '' !!}">Menunggu</a>
        <a href="?kuis={!! (int) $quiz['id'] !!}&status=selesai" class="{!! $status === 'selesai' ? 'aktif' : '' !!}">Selesai</a>
      </nav>
    </div>
    @if (count($rows))
      <div class="tabel-wrap"><table class="tabel">
        <thead><tr><th>Peserta</th><th>Dikumpulkan</th><th class="num">Benar</th><th class="num">Nilai</th><th class="num">Durasi</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
        <tbody>
          @foreach ($rows as $r)
            <tr>
              <td><div class="orang" style="min-width:0"><span><b>{{ $r->user->nama }}</b><small>{{ $r->user->email }}</small></span></div></td>
              <td>{{ tanggal($r['selesai_pada'], true) }}</td>
              <td class="num">{!! (int) $r['jumlah_benar'] !!}</td>
              <td class="num">{!! $r['skor'] !== null ? '<b>' . angka($r['skor']) . '</b>' : '–' !!}</td>
              <td class="num">{{ durasi($r['durasi_detik'] !== null ? (int) $r['durasi_detik'] : null) }}</td>
              <td><span class="status status-{{ $r['status'] }}">{{ STATUS_ATTEMPT[$r['status']] }}</span></td>
              <td><div class="aksi"><a class="btn btn-kecil {!! $r['status'] === 'menunggu' ? 'btn-kuning' : 'btn-putih' !!}" href="{{ route('pengajar.nilai', $r['id']) }}">{!! $r['status'] === 'menunggu' ? 'Nilai esai' : 'Detail' !!}</a></div></td>
            </tr>
          @endforeach
        </tbody>
      </table></div>
    @else
      @include('partials.kosong', ['judul' => 'Belum ada pengerjaan', 'pesan' => $quiz['status'] === 'terbit' ? 'Hasil peserta akan muncul di sini.' : 'Terbitkan kuis agar peserta dapat mengerjakannya.'])
    @endif
  </section>

  <section class="kartu">
    <div class="kartu-judul"><h2>{!! icon('chart') !!} Tingkat benar per soal</h2></div>
    @if ((int) ($stat['percobaan'] ?? 0) > 0)
      <div class="bar-list">
        @php foreach ($perSoal as $i => $s): $p = $s['dijawab'] ? $s['benar'] / $s['dijawab'] * 100 : 0; @endphp
          <div class="bar-baris" style="grid-template-columns:52px 1fr 44px">
            <span title="{{ mb_strimwidth($s['teks'], 0, 120, '…') }}">Soal {!! $i + 1 !!}</span>
            <div class="progress" title="{!! (int) $s['benar'] !!} dari {!! (int) $s['dijawab'] !!} benar"><span style="width:{!! round($p) !!}%"></span></div>
            <span class="num">{!! round($p) !!}%</span>
          </div>
        @endforeach
      </div>
      <p class="kecil muted mt-1 mb-0">Soal dengan persentase rendah mungkin perlu dibahas ulang di kelas.</p>
    @else
      <p class="muted mb-0">Statistik per soal muncul setelah ada peserta yang mengumpulkan.</p>
    @endif
  </section>
</div>
@endsection
