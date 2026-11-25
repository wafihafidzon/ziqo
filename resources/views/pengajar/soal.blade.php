@extends('layouts.app')

@section('konten')
@php $totalPoin = $soal->sum('poin'); @endphp
<div class="remah"><a href="{{ kuis_induk()[1] }}">{{ kuis_induk()[0] }}</a> {!! icon('chevron') !!} <span>{{ $quiz['judul'] }}</span></div>
<div class="kepala-halaman">
  <div>
    <h1>{{ $quiz['judul'] }}</h1>
    <p><span class="status status-{{ $quiz['status'] }}">{!! $quiz['status'] === 'terbit' ? 'Terbit' : 'Draft' !!}</span> · {!! count($soal) !!} soal · {!! (int) $totalPoin !!} poin · {{ $quiz['kategori'] }}{!! $quiz['topik'] ? ' / ' . e($quiz['topik']) : '' !!}</p>
  </div>
  <div class="baris">
    <a class="btn btn-putih" href="{{ route('pengajar.kuis.edit', $quiz['id']) }}">{!! icon('edit') !!} Pengaturan</a>
    <a class="btn btn-putih" href="{{ route('kuis.show', $quiz['id']) }}">{!! icon('mata') !!} Pratinjau</a>
    <form method="post" action="{{ route('pengajar.kuis') }}">
      @csrf<input type="hidden" name="id" value="{!! (int) $quiz['id'] !!}">
      @if ($quiz['status'] === 'draft')
        <button class="btn btn-hijau" name="aksi" value="terbit" type="submit" {!! $soal->isNotEmpty() ? '' : 'disabled title="Tambahkan soal dulu"' !!}>{!! icon('kirim') !!} Terbitkan</button>
      @else
        <button class="btn btn-garis" name="aksi" value="draft" type="submit">Jadikan draft</button>
      @endif
    </form>
  </div>
</div>

@if ($adaPercobaan)
  <div class="alert alert-kuning">{!! icon('info') !!}<span>Kuis ini sudah pernah dikerjakan. Nilai yang sudah tersimpan tidak berubah jika kamu mengedit soal; perubahan berlaku untuk pengerjaan berikutnya.</span></div>
@endif

<section class="kartu mb-1">
  <div class="kartu-judul"><h2>{!! icon('plus') !!} Tambah soal</h2></div>
  <div class="baris">
    @foreach (TIPE_SOAL as $k => $l)
      <a class="btn btn-putih btn-kecil" href="{{ route('pengajar.soal.create', [$quiz['id'], 'tipe' => $k]) }}">{!! icon('plus') !!} {{ $l }}</a>
    @endforeach
  </div>
</section>

@if ($soal->isNotEmpty())
  <div class="daftar-soal">
    @php foreach ($soal as $i => $s):
      $kunci = $s->opsi->filter(fn ($o) => $o->benar)->pluck('teks')->all(); @endphp
      <article class="soal-baris">
        <span class="no">{!! $i + 1 !!}</span>
        <div>
          <div class="baris mb-1"><span class="chip">{{ TIPE_SOAL[$s['tipe']] }}</span><span class="chip w-kuning">{!! (int) $s['poin'] !!} poin</span>@if ($s['tipe'] === 'isian')<span class="chip chip-garis">Pencocokan {!! $s['pencocokan'] === 'ketat' ? 'ketat' : 'longgar' !!}</span>@endif</div>
          <p class="teks">{{ $s['teks'] }}</p>
          @if (in_array($s['tipe'], ['pg', 'pgk', 'bs'], true))
            <p class="kecil muted mb-0">Opsi: {{ $s->opsi->pluck('teks')->implode(' · ') }}</p>
          @endif
          @if ($s['tipe'] === 'esai')
            <p class="kunci-ringkas mb-0" style="color:var(--oranye)">Dinilai manual oleh pengajar</p>
          @else
            <p class="kunci-ringkas mb-0">{!! icon('cek') !!} Kunci: {{ implode(' / ', $kunci) }}</p>
          @endif
        </div>
        <div class="aksi baris" style="gap:.3rem">
          <form method="post">@csrf<input type="hidden" name="soal" value="{!! (int) $s['id'] !!}">
            <button class="btn btn-kecil btn-putih btn-ikon" name="aksi" value="naik" type="submit" {!! $i === 0 ? 'disabled' : '' !!} aria-label="Pindah ke atas" title="Pindah ke atas" style="transform:rotate(-90deg)">{!! icon('chevron') !!}</button>
            <button class="btn btn-kecil btn-putih btn-ikon" name="aksi" value="turun" type="submit" {!! $i === count($soal) - 1 ? 'disabled' : '' !!} aria-label="Pindah ke bawah" title="Pindah ke bawah" style="transform:rotate(90deg)">{!! icon('chevron') !!}</button>
          </form>
          <a class="btn btn-kecil btn-putih btn-ikon" href="{{ route('pengajar.soal.edit', [$quiz['id'], $s['id']]) }}" aria-label="Edit soal {!! $i + 1 !!}" title="Edit">{!! icon('edit') !!}</a>
          <form method="post" data-confirm="Soal nomor {!! $i + 1 !!} akan dihapus permanen." data-confirm-judul="Hapus soal?" data-confirm-ok="Hapus" data-bahaya>
            @csrf<input type="hidden" name="soal" value="{!! (int) $s['id'] !!}">
            <button class="btn btn-kecil btn-teks btn-ikon" style="--fg:var(--merah)" name="aksi" value="hapus" type="submit" aria-label="Hapus soal {!! $i + 1 !!}" title="Hapus">{!! icon('sampah') !!}</button>
          </form>
        </div>
      </article>
    @endforeach
  </div>
@else
  <div class="kartu">@include('partials.kosong', ['judul' => 'Belum ada soal', 'pesan' => 'Pilih jenis soal di atas untuk menambahkan soal pertama.'])</div>
@endif
@endsection
