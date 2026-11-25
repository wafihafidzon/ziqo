@extends('layouts.app')

@section('konten')
<div class="kepala-halaman">
  <div><h1>Kuis saya</h1><p>Buat, terbitkan, dan pantau kuis yang kamu kelola.</p></div>
  <a class="btn" href="{{ route('pengajar.kuis.create') }}">{!! icon('plus') !!} Buat kuis baru</a>
</div>

<div class="grid grid-4 mb-1">
  <div class="stat w-ungu"><span class="stat-ikon">{!! icon('quiz') !!}</span><span class="stat-nilai">{!! (int) $stat['kuis'] !!}</span><span class="stat-label">Total kuis ({!! (int) $stat['terbit'] !!} terbit)</span></div>
  <div class="stat w-biru"><span class="stat-ikon">{!! icon('pengguna') !!}</span><span class="stat-nilai">{!! (int) $stat['peserta'] !!}</span><span class="stat-label">Peserta unik</span></div>
  <div class="stat w-hijau"><span class="stat-ikon">{!! icon('target') !!}</span><span class="stat-nilai">{!! $stat['rata'] !== null ? angka($stat['rata'], 1) : '–' !!}</span><span class="stat-label">Rata-rata nilai peserta</span></div>
  <div class="stat w-kuning"><span class="stat-ikon">{!! icon('pena') !!}</span><span class="stat-nilai">{!! (int) $stat['menunggu'] !!}</span><span class="stat-label">Esai menunggu dinilai</span><a class="stat-link" href="{{ route('pengajar.penilaian') }}" aria-label="Buka penilaian esai"></a></div>
</div>

<section class="kartu">
  @if ($kuis->isNotEmpty())
    <div class="tabel-wrap">
      <table class="tabel">
        <thead><tr><th>Judul</th><th>Kategori</th><th class="num">Soal</th><th class="num">Dikerjakan</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
        <tbody>
        @foreach ($kuis as $k)
          <tr>
            <td><a class="tebal" href="{{ route('kuis.show', $k['id']) }}">{{ $k['judul'] }}</a><br><small class="muted">{{ label(KESULITAN, $k['kesulitan']) }} · {{ label(JENJANG, $k['jenjang']) }}{!! $k['topik'] ? ' · ' . e($k['topik']) : '' !!}</small></td>
            <td><span class="chip {!! warna($k['warna']) !!}">{{ $k['kategori'] }}</span></td>
            <td class="num">{!! (int) $k['jumlah_soal'] !!}</td>
            <td class="num">{!! (int) $k['jumlah_percobaan'] !!}</td>
            <td><span class="status status-{{ $k['status'] }}">{!! $k['status'] === 'terbit' ? 'Terbit' : 'Draft' !!}</span></td>
            <td>
              <div class="aksi">
                <a class="btn btn-kecil" href="{{ route('pengajar.soal', $k['id']) }}">{!! icon('soal') !!} Soal</a>
                <a class="btn btn-kecil btn-putih" href="{{ route('pengajar.hasil', $k['id']) }}">{!! icon('chart') !!} Hasil</a>
                <a class="btn btn-kecil btn-putih" href="{{ route('pengajar.room', $k['id']) }}">{!! icon('kunci') !!} Room</a>
                <a class="btn btn-kecil btn-putih btn-ikon" href="{{ route('pengajar.kuis.edit', $k['id']) }}" aria-label="Edit pengaturan {{ $k['judul'] }}" title="Edit pengaturan">{!! icon('edit') !!}</a>
                <form method="post">
                  @csrf<input type="hidden" name="id" value="{!! (int) $k['id'] !!}">
                  @if ($k['status'] === 'terbit')
                    <button class="btn btn-kecil btn-garis" name="aksi" value="draft" type="submit">Jadikan draft</button>
                  @else
                    <button class="btn btn-kecil btn-hijau" name="aksi" value="terbit" type="submit">Terbitkan</button>
                  @endif
                </form>
                @if ((int) $k['jumlah_percobaan'] === 0)
                <form method="post" data-confirm="Kuis beserta seluruh soalnya akan dihapus permanen." data-confirm-judul="Hapus kuis?" data-confirm-ok="Hapus" data-bahaya>
                  @csrf<input type="hidden" name="id" value="{!! (int) $k['id'] !!}">
                  <button class="btn btn-kecil btn-teks btn-ikon" style="--fg:var(--merah)" name="aksi" value="hapus" type="submit" aria-label="Hapus {{ $k['judul'] }}" title="Hapus">{!! icon('sampah') !!}</button>
                </form>
                @endif
              </div>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @else
    @include('partials.kosong', ['judul' => 'Belum ada kuis', 'pesan' => 'Buat kuis pertamamu, tambahkan soal, lalu terbitkan agar peserta bisa mengerjakannya.', 'aksi' => ['Buat kuis', route('pengajar.kuis.create')]])
  @endif
</section>
@endsection
