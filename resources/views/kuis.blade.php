@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('konten')
@php
$publik = !auth()->check();
$estimasi = estimasi_menit($quiz);
@endphp
@if ($publik)<div class="wadah" style="padding:2rem 0 3rem">@endif

<div class="remah"><a href="{{ route('jelajah') }}">Jelajah</a> {!! icon('chevron') !!} <a href="{{ route('jelajah', kueri(['kategori' => $quiz['kategori_slug']])) }}">{{ $quiz['kategori'] }}</a> {!! icon('chevron') !!} <span>{{ $quiz['judul'] }}</span></div>

<section class="detail-hero {!! warna($quiz['warna']) !!}">
  <svg class="deko" viewBox="0 0 100 100" aria-hidden="true"><text x="20" y="90" font-family="Titan One, sans-serif" font-size="110" fill="currentColor">?</text></svg>
  <div style="position:relative">
    <div class="baris">
      <span class="chip" style="background:#fff;color:var(--w)">{{ $quiz['kategori'] }}</span>
      @if ($quiz['topik'])<span class="chip" style="background:rgba(255,255,255,.6);color:var(--teks-2)">{{ $quiz['topik'] }}</span>@endif
      @if ($quiz['status'] === 'draft')<span class="chip w-oranye">Draft</span>@endif
    </div>
    <h1>{{ $quiz['judul'] }}</h1>
    @if ($quiz['deskripsi'])<p>{{ $quiz['deskripsi'] }}</p>@endif
    <p class="kecil mt-1 mb-0">Dibuat oleh <b>{{ $quiz['pembuat'] }}</b> · {{ tanggal($quiz['created_at']) }}</p>
  </div>
  <div style="position:relative" class="tumpuk">
    @if ($quiz['status'] !== 'terbit')
      <div class="alert alert-kuning mb-0">{!! icon('info') !!} Draft: belum dapat dikerjakan peserta.</div>
    @elseif ((int) $quiz['jumlah_soal'] === 0)
      <div class="alert alert-kuning mb-0">{!! icon('info') !!} Kuis belum memiliki soal.</div>
    @elseif ($publik)
      <a class="btn btn-besar" href="{{ route('login') }}">{!! icon('login') !!} Masuk untuk mulai</a>
    @elseif (!empty($mengajar))
      @if ($bisaKelola)<a class="btn btn-besar" href="{{ route('pengajar.hasil', $quiz['id']) }}">{!! icon('chart') !!} Lihat hasil peserta</a>@endif
    @elseif ($quiz->hanyaRoom() && !$berlangsung)
      <div class="alert alert-kuning mb-0">{!! icon('kunci') !!} Kuis ini hanya bisa dikerjakan lewat room.</div>
      <a class="btn btn-besar" href="{{ route('room') }}">{!! icon('kunci') !!} Masuk room</a>
    @else
      <form method="post">
        @csrf
        <button class="btn btn-besar" type="submit">{!! icon('kilat') !!} {!! $berlangsung ? 'Lanjutkan pengerjaan' : ($riwayatSaya->isNotEmpty() ? 'Kerjakan lagi' : 'Mulai kuis') !!}</button>
      </form>
    @endif
    @if ($bisaKelola)
      <a class="btn btn-putih" href="{{ route('pengajar.soal', $quiz['id']) }}">{!! icon('edit') !!} Kelola soal</a>
    @endif
  </div>
</section>

<div class="grid grid-sidebar mt-2">
  <div class="tumpuk">
    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('info') !!} Informasi kuis</h2></div>
      <div class="info-grid">
        <div class="info">{!! icon('soal') !!}<div><small>Jumlah soal</small><b>{!! (int) $quiz['jumlah_soal'] !!} soal</b></div></div>
        <div class="info">{!! icon('jam') !!}<div><small>{!! (int) $quiz['durasi_menit'] ? 'Batas waktu' : 'Estimasi durasi' !!}</small><b>{!! $estimasi !!} menit</b></div></div>
        <div class="info">{!! icon('target') !!}<div><small>Kesulitan</small><b>{{ label(KESULITAN, $quiz['kesulitan']) }}</b></div></div>
        <div class="info">{!! icon('buku') !!}<div><small>Jenjang</small><b>{{ label(JENJANG, $quiz['jenjang']) }}</b></div></div>
        <div class="info">{!! icon('bintang') !!}<div><small>Total poin</small><b>{!! (int) $totalPoin !!} XP</b></div></div>
        <div class="info">{!! icon('pengguna') !!}<div><small>Dikerjakan</small><b>{!! (int) ($statKuis['percobaan'] ?? 0) !!}× oleh {!! (int) ($statKuis['peserta'] ?? 0) !!} orang</b></div></div>
      </div>
      <h3 class="mt-2" style="font-size:1rem">Jenis soal</h3>
      <div class="baris">
        @foreach ($tipe as $t => $n)<span class="chip">{{ TIPE_SOAL[$t] }} × {!! (int) $n !!}</span>@endforeach
        @if (!$tipe)<span class="muted">Belum ada soal.</span>@endif
      </div>
    </section>

    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('buku') !!} Petunjuk pengerjaan</h2></div>
      <ol class="petunjuk">
        @if ((int) $quiz['durasi_menit'])
          <li>Waktu pengerjaan <b>{!! (int) $quiz['durasi_menit'] !!} menit</b>. Saat waktu habis, jawaban yang tersimpan dikumpulkan otomatis.</li>
        @else
          <li>Tidak ada batas waktu, tetapi durasi pengerjaanmu tetap dicatat.</li>
        @endif
        <li>{!! (int) $quiz['navigasi_bebas'] ? 'Kamu bebas berpindah ke soal mana pun lewat nomor soal.' : 'Soal dikerjakan berurutan. Jawaban terkunci saat kamu lanjut ke soal berikutnya.' !!}</li>
        @if ((int) $quiz['tampilkan_feedback'])
          <li>Tekan <b>Cek jawaban</b> untuk melihat benar/salah dan pembahasan. Jawaban yang sudah dicek tidak bisa diubah.</li>
        @else
          <li>Hasil dan pembahasan ditampilkan setelah kuis selesai.</li>
        @endif
        <li>Pilihan ganda kompleks: nilai penuh hanya jika <b>semua</b> pilihan benar terpilih dan tidak ada pilihan salah.</li>
        @if (isset($tipe['esai']))<li>Soal esai dinilai manual oleh pengajar. Nilai akhir muncul setelah penilaian selesai.</li>@endif
        <li>Jawaban tersimpan otomatis. Jika halaman tertutup, kamu bisa melanjutkan dari halaman ini.</li>
      </ol>
      @if ($quiz['petunjuk'])<div class="pembahasan mt-1"><b>Catatan dari pengajar</b>{!! nl2br(e($quiz['petunjuk'])) !!}</div>@endif
    </section>
  </div>

  <div class="tumpuk">
    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('trofi') !!} Papan peringkat</h2></div>
      @if ($peringkat->isNotEmpty())
        <ol class="peringkat">
          @foreach ($peringkat as $i => $p)
            <li><span class="no">{!! $i + 1 !!}</span><img class="avatar" style="width:32px;height:32px" src="{{ avatar_url($p) }}" alt=""><span class="tebal kecil">{{ $p['nama'] }}</span><span class="skor">{!! angka($p['skor_terbaik']) !!}</span></li>
          @endforeach
        </ol>
      @else
        <p class="muted mb-0">Belum ada yang menyelesaikan kuis ini. Jadilah yang pertama!</p>
      @endif
    </section>
    @if ($riwayatSaya->isNotEmpty())
    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('riwayat') !!} Pengerjaanmu</h2></div>
      <ul class="aktivitas">
        @foreach ($riwayatSaya as $a)
          <li class="{!! warna_skor($a['skor']) !!}"><span class="bulat">{!! icon('quiz') !!}</span>
            <div class="isi"><b><a href="{{ route('hasil', $a['id']) }}" style="color:inherit">{{ tanggal($a['selesai_pada'], true) }}</a></b><small>{{ durasi($a['durasi_detik'] !== null ? (int) $a['durasi_detik'] : null) }}</small></div>
            {!! $a['status'] === 'selesai' ? '<span class="skor-pil">' . angka($a['skor']) . '</span>' : '<span class="status status-menunggu">Menunggu</span>' !!}</li>
        @endforeach
      </ul>
    </section>
    @endif
  </div>
</div>

@if ($lainnya->isNotEmpty())
  <h2 class="mt-2" style="font-size:1.15rem">Kuis {{ $quiz['kategori'] }} lainnya</h2>
  <div class="grid-kuis">@foreach ($lainnya as $k)
    @include('partials.kuis-kartu', ['k' => $k])
  @endforeach</div>
@endif

@if ($publik)</div>@endif
@endsection
