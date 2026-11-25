@extends('layouts.app')

@section('konten')
@php
$namaDepan = explode(' ', trim($user['nama']))[0];
$mengajar = $mengajar ?? false;
if (!$mengajar) {
    $adaData = (int) $stat['total'] > 0;
    $totalJawab = (int) $stat['benar'] + (int) $stat['salah'];
}
@endphp
<section class="sambutan">
  @include('partials.deko', ['varian' => 'gelap'])
  <div class="isi">
    <h1>{{ salam() }}, {{ $namaDepan }}!</h1>
    @if ($mengajar)
    <p>Pantau kuis, room, dan perkembangan pesertamu dari sini.</p>
    @else
    <p>{!! $adaData ? 'Kamu sudah menyelesaikan ' . (int) $stat['total'] . ' kuis. Pertahankan semangat belajarmu!' : 'Ayo mulai kuis pertamamu dan raih badge First Step.' !!}</p>
    <div class="level-kotak">
      <span class="lvl" title="Level">{!! (int) $level['level'] !!}</span>
      <div style="flex:1">
        <div class="baris-antara"><small>Level {!! (int) $level['level'] !!} · {!! angka($stat['xp']) !!} XP</small><small>{!! angka($level['progres']) !!}/{!! (int) $level['butuh'] !!} ke level {!! (int) $level['level'] + 1 !!}</small></div>
        <div class="progress kuning" role="progressbar" aria-label="Kemajuan level" aria-valuemin="0" aria-valuemax="{!! (int) $level['butuh'] !!}" aria-valuenow="{!! (int) $level['progres'] !!}"><span style="width:{!! round($level['progres'] / $level['butuh'] * 100) !!}%"></span></div>
      </div>
    </div>
    @endif
  </div>
  <img class="maskot" src="{{ asset('assets/maskot.webp') }}" alt="">
</section>

@unless ($mengajar)
<div class="grid grid-4 mt-2">
  <div class="stat w-ungu"><span class="stat-ikon">{!! icon('quiz') !!}</span><span class="stat-nilai">{!! (int) $stat['total'] !!}</span><span class="stat-label">Kuis diselesaikan{!! (int) $stat['menunggu'] ? ' · ' . (int) $stat['menunggu'] . ' menunggu nilai' : '' !!}</span></div>
  <div class="stat w-biru"><span class="stat-ikon">{!! icon('target') !!}</span><span class="stat-nilai">{!! $adaData ? angka($stat['rata'], 1) : '–' !!}</span><span class="stat-label">Rata-rata nilai</span></div>
  <div class="stat w-kuning"><span class="stat-ikon">{!! icon('bintang') !!}</span><span class="stat-nilai">{!! $adaData ? angka($stat['tertinggi']) : '–' !!}</span><span class="stat-label">Nilai tertinggi</span></div>
  <div class="stat w-hijau"><span class="stat-ikon">{!! icon('cek') !!}</span><span class="stat-nilai">{!! (int) $stat['benar'] !!}<small> / {!! (int) $stat['salah'] !!} salah</small></span><span class="stat-label">Jawaban benar{!! $totalJawab ? ' (' . round($stat['benar'] / $totalJawab * 100) . '% akurasi)' : '' !!}</span></div>
</div>
@endunless

@if (isset($guru))
<section class="kartu mt-2">
  <div class="kartu-judul">
    <h2>{!! icon('pena') !!} Ruang mengajar</h2>
    <a class="btn btn-kecil" href="{{ route('pengajar.kuis.create') }}">{!! icon('plus') !!} Buat kuis</a>
  </div>
  <div class="grid grid-4">
    <div class="info">{!! icon('quiz') !!}<div><small>Kuis saya</small><b>{!! (int) $guru['kuis'] !!} ({!! (int) $guru['terbit'] !!} terbit)</b></div></div>
    <div class="info">{!! icon('pengguna') !!}<div><small>Peserta unik</small><b>{!! (int) $guru['peserta'] !!}</b></div></div>
    <div class="info">{!! icon('riwayat') !!}<div><small>Total pengerjaan</small><b>{!! (int) $guru['percobaan'] !!}</b></div></div>
    <div class="info">{!! icon('target') !!}<div><small>Rata-rata peserta</small><b>{!! $guru['rata'] !== null ? angka($guru['rata'], 1) : '–' !!}</b></div></div>
  </div>
</section>
@endif

@if ($mengajar && $esai->isNotEmpty())
<section class="kartu mt-2">
  <div class="kartu-judul"><h2>{!! icon('pena') !!} {!! count($esai) !!} pengerjaan menunggu penilaian esai</h2><a href="{{ route('pengajar.penilaian') }}" class="kecil tebal">Semua</a></div>
  @if (isset($platform))<p class="muted">Penilaian biasanya dilakukan pengajar pembuat kuis. Nilai di sini hanya sebagai cadangan.</p>@endif
    <ul class="aktivitas">
      @foreach ($esai as $a)
        <li class="w-kuning"><span class="bulat">{!! icon('pena') !!}</span>
          <div class="isi"><b>{{ $a['nama'] }} · {{ $a['judul'] }}</b><small>Dikumpulkan {{ tanggal($a['selesai_pada'], true) }}</small></div>
          <a class="btn btn-kecil btn-kuning" href="{{ route('pengajar.nilai', $a['id']) }}">Nilai</a></li>
      @endforeach
    </ul>
</section>
@endif

@if ($mengajar)
<section class="kartu mt-2">
  <div class="kartu-judul"><h2>{!! icon('kunci') !!} Room aktif</h2><a href="{{ route('pengajar.rooms') }}" class="kecil tebal">Semua room</a></div>
  @if ($roomAktif->isNotEmpty())
    <ul class="aktivitas">
      @foreach ($roomAktif as $r)
        <li class="w-hijau"><span class="bulat">{!! icon('kunci') !!}</span>
          <div class="isi"><b><span style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.12em">{{ $r->kode_akses }}</span> · {{ $r->quiz?->judul }}</b>
            <small>{!! (int) $r->attempts_count !!} peserta · {!! (int) $r->sedang_count !!} sedang mengerjakan</small></div>
          <a class="btn btn-kecil btn-putih" href="{{ route('pengajar.room', [$r->quiz_id, 'room' => $r->id]) }}">Peserta</a></li>
      @endforeach
    </ul>
  @else
    <p class="muted mb-0">Tidak ada room yang sedang dibuka. Buat room dari menu <a href="{{ kuis_induk()[1] }}">{{ isset($platform) ? 'Pantau Kuis' : 'Kuis Saya' }}</a>.</p>
  @endif
</section>
@endif

@if (isset($platform))
<section class="kartu mt-2">
  <div class="kartu-judul"><h2>{!! icon('perisai') !!} Statistik platform</h2><a class="btn btn-kecil btn-putih" href="{{ route('admin.pengguna') }}">Kelola pengguna</a></div>
  <div class="grid grid-sidebar">
    <div>
      <p class="kecil muted mb-0">Kuis diselesaikan per hari, 14 hari terakhir</p>
      {!! \App\Support\Chart::bars($harian) !!}
    </div>
    <div class="tumpuk" style="gap:.6rem">
      <div class="info">{!! icon('pengguna') !!}<div><small>Pengguna</small><b>{!! (int) array_sum($platform['pengguna']) !!} ({!! (int) ($platform['pengguna']['peserta'] ?? 0) !!} peserta, {!! (int) ($platform['pengguna']['pengajar'] ?? 0) !!} pengajar)</b></div></div>
      <div class="info">{!! icon('quiz') !!}<div><small>Kuis terbit / draft</small><b>{!! (int) $platform['kuis'] !!} / {!! (int) $platform['draft'] !!}</b></div></div>
      <div class="info">{!! icon('cek') !!}<div><small>Pengerjaan selesai</small><b>{!! (int) $platform['percobaan'] !!} · rata-rata {!! $platform['rata'] !== null ? angka($platform['rata'], 1) : '–' !!}</b></div></div>
      <div class="info">{!! icon('power') !!}<div><small>Akun nonaktif</small><b>{!! (int) $platform['nonaktif'] !!}</b></div></div>
    </div>
  </div>
</section>
@endif

@unless ($mengajar)
<div class="grid grid-sidebar mt-2">
  <div class="tumpuk">
    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('chart') !!} Perkembangan nilai</h2><span class="kecil muted">{!! count($trend) !!} kuis terakhir</span></div>
      @if (count($trend) >= 1)
        {!! \App\Support\Chart::line($trend) !!}
      @else
        @include('partials.kosong', ['judul' => 'Grafik masih kosong', 'pesan' => 'Selesaikan kuis untuk melihat perkembangan nilaimu di sini.', 'aksi' => ['Cari kuis', route('jelajah')]])
      @endif
    </section>

    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('kategori') !!} Nilai per mata pelajaran</h2></div>
      @if ($perKategori->isNotEmpty())
        <div class="bar-list">
          @foreach ($perKategori as $c)
            <div class="bar-baris">
              <span>{{ $c['nama'] }} <small class="muted">({!! (int) $c['jumlah'] !!}×)</small></span>
              <div class="progress" title="Rata-rata {{ angka($c['rata'], 1) }}"><span style="width:{!! round($c['rata']) !!}%"></span></div>
              <span class="num">{!! angka($c['rata'], 1) !!}</span>
            </div>
          @endforeach
        </div>
      @else
        <p class="muted mb-0">Belum ada data. Distribusi nilai per mata pelajaran muncul setelah kamu menyelesaikan kuis.</p>
      @endif
    </section>

    <section>
      <div class="baris-antara mb-1"><h2 style="font-size:1.15rem;margin:0" class="baris">{!! icon('kilat') !!} Rekomendasi untukmu</h2><a href="{{ route('jelajah', kueri(['sort' => 'rekomendasi'])) }}" class="tebal">Lihat lainnya</a></div>
      @if ($rekomendasi->isNotEmpty())
        <div class="grid-kuis">@foreach ($rekomendasi as $k)
    @include('partials.kuis-kartu', ['k' => $k])
  @endforeach</div>
      @else
        <div class="kartu">@include('partials.kosong', ['judul' => 'Belum ada kuis'])</div>
      @endif
    </section>
  </div>

  <div class="tumpuk">
    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('trofi') !!} Badge</h2><a href="{{ route('pencapaian') }}" class="kecil tebal">Semua</a></div>
      @if ($badges->isNotEmpty())
        <div class="baris">
          @foreach ($badges->take(6) as $b)
            <span class="lencana-mini {!! warna($b['warna']) !!}" title="{{ $b['nama'] . ': ' . $b['deskripsi'] }}">{!! icon($b['ikon']) !!}</span>
          @endforeach
        </div>
        <p class="kecil muted mt-1 mb-0">{!! count($badges) !!} badge diperoleh</p>
      @else
        <p class="muted mb-0">Belum ada badge. Selesaikan kuis pertamamu untuk meraih <b>First Step</b>.</p>
      @endif
    </section>

    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('riwayat') !!} Aktivitas terbaru</h2><a href="{{ route('riwayat') }}" class="kecil tebal">Riwayat</a></div>
      @if ($aktivitas->isNotEmpty())
        <ul class="aktivitas">
          @php foreach ($aktivitas as $a):
            $href = $a['status'] === 'berlangsung' ? route('kerjakan', $a['id']) : route('hasil', $a['id']); @endphp
            <li class="{!! warna($a['warna']) !!}">
              <span class="bulat">{!! icon($a['status'] === 'berlangsung' ? 'jam' : 'quiz') !!}</span>
              <div class="isi"><b><a href="{{ $href }}" style="color:inherit">{{ $a['judul'] }}</a></b>
                <small>{{ $a['kategori'] }} · {{ tanggal($a['selesai_pada'] ?? $a['mulai_pada'], true) }}</small></div>
              @if ($a['status'] === 'selesai')<span class="skor-pil">{!! angka($a['skor']) !!}</span>
              @else<span class="status status-{{ $a['status'] }}">{{ STATUS_ATTEMPT[$a['status']] }}</span>@endif
            </li>
          @endforeach
        </ul>
      @else
        <p class="muted mb-0">Aktivitas belajarmu akan tampil di sini.</p>
      @endif
    </section>
  </div>
</div>
@endunless
@endsection
