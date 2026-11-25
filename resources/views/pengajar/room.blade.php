@extends('layouts.app')

@section('konten')
@php $gayaKode = 'font-family:ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.12em'; @endphp
<div class="remah"><a href="{{ kuis_induk()[1] }}">{{ kuis_induk()[0] }}</a> {!! icon('chevron') !!} <span>{{ $quiz['judul'] }}</span></div>
<div class="kepala-halaman">
  <div><h1>Room kuis</h1><p>Buat sesi dengan kode akses agar peserta di kelasmu masuk ke kuis yang sama.</p></div>
  <form method="post">
    @csrf
    <button class="btn" name="aksi" value="buat" type="submit">{!! icon('plus') !!} Buat room baru</button>
  </form>
</div>

@if ($quiz['status'] !== 'terbit')
  <p class="petunjuk mb-1">{!! icon('info') !!} Kuis masih draft. Terbitkan kuis agar room bisa dibuat dan dikerjakan.</p>
@endif

<section class="kartu mb-1">
  <div class="kartu-judul"><h2>{!! icon('kunci') !!} Daftar room</h2></div>
  @if ($rooms->isNotEmpty())
    <div class="tabel-wrap"><table class="tabel">
      <thead><tr><th>Kode akses</th><th>Status</th><th class="num">Peserta</th><th>Dibuat</th><th><span class="sr-only">Aksi</span></th></tr></thead>
      <tbody>
      @foreach ($rooms as $r)
        <tr>
          <td><b style="{{ $gayaKode }}">{{ $r->kode_akses }}</b></td>
          <td><span class="status status-{{ $r->status === 'buka' ? 'aktif' : 'nonaktif' }}">{{ \App\Models\Room::STATUS[$r->status] }}</span></td>
          <td class="num">{!! (int) $r->attempts_count !!}</td>
          <td>{{ tanggal((string) $r->created_at, true) }}</td>
          <td>
            <div class="aksi">
              <a class="btn btn-kecil {!! $pilih?->id === $r->id ? '' : 'btn-putih' !!}" href="{{ route('pengajar.room', [$quiz['id'], 'room' => $r->id]) }}">{!! icon('pengguna') !!} Peserta</a>
              <form method="post">
                @csrf<input type="hidden" name="id" value="{!! (int) $r->id !!}">
                @if ($r->status === 'buka')
                  <button class="btn btn-kecil btn-garis" name="aksi" value="tutup" type="submit">Tutup</button>
                @else
                  <button class="btn btn-kecil btn-hijau" name="aksi" value="buka" type="submit">Buka lagi</button>
                @endif
              </form>
              @if ((int) $r->attempts_count === 0)
              <form method="post" data-confirm="Room {{ $r->kode_akses }} akan dihapus permanen." data-confirm-judul="Hapus room?" data-confirm-ok="Hapus" data-bahaya>
                @csrf<input type="hidden" name="id" value="{!! (int) $r->id !!}">
                <button class="btn btn-kecil btn-teks btn-ikon" style="--fg:var(--merah)" name="aksi" value="hapus" type="submit" aria-label="Hapus room {{ $r->kode_akses }}" title="Hapus">{!! icon('sampah') !!}</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  @else
    @include('partials.kosong', ['judul' => 'Belum ada room', 'pesan' => 'Buat room untuk mendapatkan kode akses, lalu bagikan kode itu ke peserta.'])
  @endif
</section>

@if ($pilih)
<section class="kartu">
  <div class="kartu-judul">
    <h2>{!! icon('pengguna') !!} Peserta room <b style="{{ $gayaKode }}">{{ $pilih->kode_akses }}</b></h2>
  </div>
  <p class="muted mb-1">Peserta masuk lewat menu <b>Masuk Room</b> atau tautan <a href="{{ route('room', ['kode' => $pilih->kode_akses]) }}">{{ route('room', ['kode' => $pilih->kode_akses]) }}</a></p>
  @if ($peserta->isNotEmpty())
    <div class="tabel-wrap"><table class="tabel">
      <thead><tr><th>Peserta</th><th>Mulai</th><th class="num">Benar</th><th class="num">Nilai</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
      <tbody>
      @foreach ($peserta as $a)
        <tr>
          <td><div class="orang" style="min-width:0"><span><b>{{ $a->user->nama }}</b><small>{{ $a->user->email }}</small></span></div></td>
          <td>{{ tanggal($a['mulai_pada'], true) }}</td>
          <td class="num">{!! $a['jumlah_benar'] !== null ? (int) $a['jumlah_benar'] : '–' !!}</td>
          <td class="num">{!! $a['skor'] !== null ? '<b>' . angka($a['skor']) . '</b>' : '–' !!}</td>
          <td><span class="status status-{{ $a['status'] }}">{{ STATUS_ATTEMPT[$a['status']] }}</span></td>
          <td>
            @if ($a['status'] !== 'berlangsung')
              <div class="aksi"><a class="btn btn-kecil {!! $a['status'] === 'menunggu' ? 'btn-kuning' : 'btn-putih' !!}" href="{{ route('pengajar.nilai', $a['id']) }}">{!! $a['status'] === 'menunggu' ? 'Nilai esai' : 'Detail' !!}</a></div>
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  @else
    @include('partials.kosong', ['judul' => 'Belum ada peserta', 'pesan' => 'Peserta yang masuk dengan kode ini akan muncul di sini.'])
  @endif
</section>
@endif
@endsection
