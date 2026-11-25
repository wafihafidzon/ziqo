@extends('layouts.app')

@section('konten')
<div class="kepala-halaman">
  <div>
    <h1>Riwayat pengerjaan</h1>
    <p>Semua kuis yang pernah kamu selesaikan, lengkap dengan nilai dan durasinya.</p>
  </div>
  @if ($adaRiwayat)
    <button class="btn btn-garis" type="button" data-buka-reset>{!! icon('reset') !!} Reset riwayat</button>
  @endif
</div>

<form class="kartu mb-1" method="get" data-auto-kirim aria-label="Filter riwayat">
  <div class="baris-form">
    <div class="field mb-0">
      <label class="label" for="r-kat">Mata pelajaran</label>
      <select class="input" id="r-kat" name="kategori">
        <option value="">Semua</option>
        @foreach ($kategori as $c)<option value="{{ $c['slug'] }}" {!! $f['kategori'] === $c['slug'] ? 'selected' : '' !!}>{{ $c['nama'] }}</option>@endforeach
      </select>
    </div>
    <div class="field mb-0"><label class="label" for="r-dari">Dari tanggal</label><input class="input" type="date" id="r-dari" name="dari" value="{{ $f['dari'] }}"></div>
    <div class="field mb-0"><label class="label" for="r-sampai">Sampai tanggal</label><input class="input" type="date" id="r-sampai" name="sampai" value="{{ $f['sampai'] }}"></div>
    <div class="field mb-0">
      <label class="label" for="r-nilai">Nilai</label>
      <select class="input" id="r-nilai" name="nilai">
        <option value="">Semua nilai</option>
        <option value="tinggi" {!! $f['nilai'] === 'tinggi' ? 'selected' : '' !!}>Tinggi (≥ 80)</option>
        <option value="sedang" {!! $f['nilai'] === 'sedang' ? 'selected' : '' !!}>Sedang (60–79)</option>
        <option value="rendah" {!! $f['nilai'] === 'rendah' ? 'selected' : '' !!}>Rendah (&lt; 60)</option>
        <option value="menunggu" {!! $f['nilai'] === 'menunggu' ? 'selected' : '' !!}>Menunggu penilaian</option>
      </select>
    </div>
  </div>
  <div class="aksi-form"><button class="btn btn-kecil" type="submit">{!! icon('filter') !!} Terapkan</button>@if (array_filter($f))<a class="btn btn-teks btn-kecil" href="{{ route('riwayat') }}">Hapus filter</a>@endif</div>
</form>

<section class="kartu">
  @if (count($rows))
    <div class="tabel-wrap">
      <table class="tabel">
        <thead><tr><th>Kuis</th><th>Mata pelajaran</th><th>Tanggal</th><th class="num">Nilai</th><th class="num">Durasi</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
        <tbody>
          @foreach ($rows as $r)
            <tr>
              <td class="tebal">{{ $r->quiz->judul }}</td>
              <td><span class="chip {!! warna($r->quiz->category->warna) !!}">{{ $r->quiz->category->nama }}</span></td>
              <td>{{ tanggal($r['selesai_pada'], true) }}</td>
              <td class="num">{!! $r['skor'] !== null ? '<b class="skor-pil">' . angka($r['skor']) . '</b>' : '–' !!}</td>
              <td class="num">{{ durasi($r['durasi_detik'] !== null ? (int) $r['durasi_detik'] : null) }}</td>
              <td><span class="status status-{{ $r['status'] }}">{{ STATUS_ATTEMPT[$r['status']] }}</span></td>
              <td><div class="aksi"><a class="btn btn-kecil btn-putih" href="{{ route('hasil', $r['id']) }}">Detail</a></div></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @include('partials.paginasi', ['pg' => $pg])
  @elseif ($adaRiwayat)
    @include('partials.kosong', ['judul' => 'Tidak ada yang cocok', 'pesan' => 'Tidak ada riwayat yang sesuai filter.', 'aksi' => ['Hapus filter', route('riwayat')]])
  @else
    @include('partials.kosong', ['judul' => 'Riwayat masih kosong', 'pesan' => 'Kuis yang kamu selesaikan akan tercatat di sini.', 'aksi' => ['Mulai kuis', route('jelajah')]])
  @endif
</section>

<dialog class="dialog" id="dialog-reset" aria-labelledby="reset-judul">
  <div class="dialog-ikon bahaya">{!! icon('reset') !!}</div>
  <h3 id="reset-judul">Reset riwayat pribadi?</h3>
  <p>Semua riwayat dan statistik pribadimu akan dimulai dari nol. Riwayat lama <b>diarsipkan</b> (tidak dihapus) agar pengajar tetap bisa melihat hasil di kuisnya. Akun, badge, dan pengguna lain tidak terpengaruh.</p>
  <form method="post">
    @csrf
    <input type="hidden" name="aksi" value="reset">
    <div class="field" style="text-align:left">
      <label for="konfirmasi_teks">Ketik <b>RESET</b> untuk melanjutkan</label>
      <input class="input" id="konfirmasi_teks" name="konfirmasi_teks" autocomplete="off" required pattern="RESET" title="Ketik RESET dengan huruf kapital">
    </div>
    <div class="dialog-aksi">
      <button type="button" class="btn btn-garis" data-tutup-reset>Batal</button>
      <button type="submit" class="btn btn-merah">Reset riwayat</button>
    </div>
  </form>
</dialog>
<script>
  (function () {
    const d = document.getElementById('dialog-reset');
    document.querySelector('[data-buka-reset]')?.addEventListener('click', () => d.showModal());
    d.querySelector('[data-tutup-reset]').addEventListener('click', () => d.close());
  })();
</script>
@endsection
