@extends('layouts.app')

@section('konten')
<div class="kepala-halaman"><div><h1>Pantau kuis</h1><p>Seluruh kuis dari semua pengajar. Administrator dapat menurunkan (draft) atau menghapus kuis yang belum pernah dikerjakan.</p></div></div>

<form class="kartu mb-1" method="get" data-auto-kirim role="search" aria-label="Filter kuis">
  <div class="baris-form baris-filter">
    <div class="field mb-0"><label class="label" for="k-q">Cari judul atau pembuat</label><div class="input-ikon">{!! icon('cari') !!}<input class="input" id="k-q" type="search" name="q" value="{{ $f['q'] }}"></div></div>
    <div class="field mb-0"><label class="label" for="k-kat">Kategori</label><select class="input" id="k-kat" name="kategori"><option value="">Semua</option>@foreach ($kategori as $c)<option value="{{ $c['slug'] }}" {!! $f['kategori'] === $c['slug'] ? 'selected' : '' !!}>{{ $c['nama'] }}</option>@endforeach</select></div>
    <div class="field mb-0"><label class="label" for="k-st">Status</label><select class="input" id="k-st" name="status"><option value="">Semua</option><option value="terbit" {!! $f['status'] === 'terbit' ? 'selected' : '' !!}>Terbit</option><option value="draft" {!! $f['status'] === 'draft' ? 'selected' : '' !!}>Draft</option></select></div>
    <button class="btn" type="submit">{!! icon('cari') !!} Cari</button>
  </div>
</form>

<section class="kartu">
  <p class="kecil muted">{!! (int) $pg['total'] !!} kuis</p>
  @if ($rows->isNotEmpty())
    <div class="tabel-wrap"><table class="tabel">
      <thead><tr><th>Kuis</th><th>Pembuat</th><th>Kategori</th><th class="num">Soal</th><th class="num">Dikerjakan</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
      <tbody>
        @foreach ($rows as $k)
          <tr>
            <td><a class="tebal" href="{{ route('kuis.show', $k['id']) }}">{{ $k['judul'] }}</a><br><small class="muted">{{ label(KESULITAN, $k['kesulitan']) }} · {{ label(JENJANG, $k['jenjang']) }} · {{ tanggal($k['created_at']) }}</small></td>
            <td>{{ $k['pembuat'] }}</td>
            <td><span class="chip {!! warna($k['warna']) !!}">{{ $k['kategori'] }}</span></td>
            <td class="num">{!! (int) $k['jumlah_soal'] !!}</td>
            <td class="num">{!! (int) $k['jumlah_percobaan'] !!}</td>
            <td><span class="status status-{{ $k['status'] }}">{!! $k['status'] === 'terbit' ? 'Terbit' : 'Draft' !!}</span></td>
            <td><div class="aksi">
              <a class="btn btn-kecil btn-putih" href="{{ route('pengajar.hasil', $k['id']) }}">Hasil</a>
              <form method="post" action="{{ route('pengajar.kuis') }}" {!! $k['status'] === 'terbit' ? 'data-confirm="Kuis akan disembunyikan dari peserta sampai diterbitkan kembali." data-confirm-judul="Turunkan kuis?" data-confirm-ok="Jadikan draft"' : '' !!}>
                @csrf<input type="hidden" name="id" value="{!! (int) $k['id'] !!}"><input type="hidden" name="kembali" value="admin">
                @if ($k['status'] === 'terbit')<button class="btn btn-kecil btn-garis" name="aksi" value="draft" type="submit">Turunkan</button>
                @else<button class="btn btn-kecil btn-hijau" name="aksi" value="terbit" type="submit">Terbitkan</button>@endif
              </form>
              @if ((int) $k['jumlah_percobaan'] === 0)
                <form method="post" action="{{ route('pengajar.kuis') }}" data-confirm="Kuis &quot;{{ $k['judul'] }}&quot; dan seluruh soalnya akan dihapus permanen." data-confirm-judul="Hapus kuis?" data-confirm-ok="Hapus" data-bahaya>
                  @csrf<input type="hidden" name="id" value="{!! (int) $k['id'] !!}"><input type="hidden" name="kembali" value="admin">
                  <button class="btn btn-kecil btn-teks btn-ikon" style="--fg:var(--merah)" name="aksi" value="hapus" type="submit" aria-label="Hapus {{ $k['judul'] }}">{!! icon('sampah') !!}</button>
                </form>
              @endif
            </div></td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
    @include('partials.paginasi', ['pg' => $pg])
  @else
    @include('partials.kosong', ['judul' => 'Tidak ada kuis', 'aksi' => ['Reset filter', route('admin.kuis')]])
  @endif
</section>
@endsection
