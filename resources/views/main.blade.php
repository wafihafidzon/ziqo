@extends('layouts.bare')

@section('konten')
<div class="main-wrap {!! warna($quiz['warna']) !!}">
  <header class="main-bar">
    <a class="btn btn-putih btn-ikon" href="{{ route('kuis.show', $quiz['id']) }}" aria-label="Keluar sementara (jawaban tersimpan)" title="Keluar sementara, jawaban tersimpan">{!! icon('panah-kiri') !!}</a>
    <div class="judul"><b>{{ $quiz['judul'] }}</b><small>{{ $quiz['kategori'] }} · <span id="info-progres">0/0 terjawab</span></small></div>
    <span class="status-simpan" id="status-simpan" aria-live="polite"></span>
    <span class="timer" id="timer" hidden aria-live="off">{!! icon('jam') !!}<span id="timer-teks">--:--</span></span>
    <button class="btn btn-hijau" type="button" id="tombol-selesai">{!! icon('cek') !!} Selesai</button>
  </header>
  <div class="main-progress" aria-hidden="true"><span id="bar-progres" style="width:0"></span></div>

  <div class="main-isi">
    <nav class="nomor-soal" id="nomor-soal" aria-label="Nomor soal"></nav>

    <section class="soal-kartu" id="soal-kartu" aria-live="polite">
      <p class="muted">Memuat soal...</p>
    </section>

    <div id="umpan-balik"></div>

    <div class="main-navigasi">
      <button class="btn btn-putih" type="button" id="tombol-sebelum">{!! icon('panah-kiri') !!} Sebelumnya</button>
      <div class="baris">
        <button class="btn btn-kuning" type="button" id="tombol-cek" hidden>{!! icon('target') !!} Cek jawaban</button>
        <button class="btn" type="button" id="tombol-lanjut">Berikutnya {!! icon('panah-kanan') !!}</button>
      </div>
    </div>
  </div>
</div>
<script id="data-kuis" type="application/json">{!! json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
