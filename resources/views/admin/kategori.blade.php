@extends('layouts.app')

@section('konten')
@php
$k = $form === 'kategori' && old() ? old() : ($editKat ?? ['nama' => '', 'warna' => 'ungu', 'deskripsi' => '']);
$t = $form === 'topik' && old() ? old() : ($editTopik ?? ['nama' => '', 'category_id' => '']);
$errK = $form === 'kategori' ? $errors : [];
$errT = $form === 'topik' ? $errors : [];
@endphp
<div class="kepala-halaman"><div><h1>Kategori &amp; topik</h1><p>Mata pelajaran dan topiknya disimpan di database dan langsung dipakai di form kuis serta filter eksplorasi.</p></div></div>

<div class="grid grid-sidebar">
  <section class="tumpuk">
    @php foreach ($kategori as $c):
      $dipakai = (int) $c->semua_kuis; @endphp
      <article class="kartu {!! warna($c['warna']) !!}" style="padding:1.15rem 1.25rem">
        <div class="baris-antara">
          <div class="baris">
            <span class="stat-ikon" style="margin:0;font-family:var(--font-judul);font-size:1.2rem">{{ mb_substr($c['nama'], 0, 1) }}</span>
            <div><b>{{ $c['nama'] }}</b><br><small class="muted">{!! $dipakai !!} kuis · slug: {{ $c['slug'] }}</small></div>
          </div>
          <div class="aksi baris" style="gap:.3rem">
            <a class="btn btn-kecil btn-putih btn-ikon" href="?edit_kategori={!! (int) $c['id'] !!}#form-kategori" aria-label="Edit {{ $c['nama'] }}" title="Edit">{!! icon('edit') !!}</a>
            @if ($dipakai === 0)
              <form method="post" data-confirm="Kategori &quot;{{ $c['nama'] }}&quot; beserta topiknya akan dihapus." data-confirm-judul="Hapus kategori?" data-confirm-ok="Hapus" data-bahaya>
                @csrf<input type="hidden" name="aksi" value="hapus-kategori"><input type="hidden" name="id" value="{!! (int) $c['id'] !!}">
                <button class="btn btn-kecil btn-teks btn-ikon" style="--fg:var(--merah)" type="submit" aria-label="Hapus {{ $c['nama'] }}" title="Hapus">{!! icon('sampah') !!}</button>
              </form>
            @else
              <span class="btn btn-kecil btn-teks btn-ikon" style="--fg:var(--teks-3);cursor:help" title="Tidak dapat dihapus: dipakai {!! $dipakai !!} kuis">{!! icon('kunci') !!}</span>
            @endif
          </div>
        </div>
        @if ($c['deskripsi'])<p class="kecil muted mt-1 mb-0">{{ $c['deskripsi'] }}</p>@endif
        <div class="baris mt-1" style="gap:.4rem">
          @foreach ($c->topics as $s)
            <span class="chip chip-garis" style="padding-right:.3rem">
              {{ $s['nama'] }} <small>({!! (int) $s['jumlah_kuis'] !!})</small>
              <a href="?edit_topik={!! (int) $s['id'] !!}#form-topik" aria-label="Edit topik {{ $s['nama'] }}" title="Edit">{!! icon('edit') !!}</a>
              <form method="post" style="display:inline" data-confirm="Topik &quot;{{ $s['nama'] }}&quot; dihapus. Kuis yang memakainya tetap ada tanpa topik." data-confirm-judul="Hapus topik?" data-confirm-ok="Hapus" data-bahaya>
                @csrf<input type="hidden" name="aksi" value="hapus-topik"><input type="hidden" name="id" value="{!! (int) $s['id'] !!}">
                <button type="submit" style="border:0;background:none;padding:0;color:var(--merah);cursor:pointer" aria-label="Hapus topik {{ $s['nama'] }}" title="Hapus">{!! icon('silang') !!}</button>
              </form>
            </span>
          @endforeach
          @if ($c->topics->isEmpty())<span class="kecil muted">Belum ada topik.</span>@endif
        </div>
      </article>
    @endforeach
  </section>

  <aside class="tumpuk">
    <section class="kartu" id="form-kategori">
      <div class="kartu-judul"><h2>{!! icon($editKat ? 'edit' : 'plus') !!} {!! $editKat ? 'Edit kategori' : 'Kategori baru' !!}</h2></div>
      <form method="post" data-validasi>
        @csrf<input type="hidden" name="aksi" value="simpan-kategori"><input type="hidden" name="id" value="{!! (int) ($k['id'] ?? 0) !!}">
        <div class="field {!! isset($errK['nama']) ? 'salah' : '' !!}"><label for="k-nama">Nama mata pelajaran</label>
          <input class="input" id="k-nama" name="nama" required minlength="2" maxlength="80" data-label="Nama" value="{{ $k['nama'] }}"{!! aria_err($errK, 'nama') !!}>{!! err($errK, 'nama') !!}</div>
        <fieldset class="field" style="border:0;padding:0"><legend class="label">Warna</legend>
          <div class="chip-pilih">@foreach (WARNA as $w)<label><input type="radio" name="warna" value="{!! $w !!}" {!! ($k['warna'] ?? 'ungu') === $w ? 'checked' : '' !!}><span class="w-{!! $w !!}" style="background:var(--wm);color:var(--w)">{!! ucfirst($w) !!}</span></label>@endforeach</div></fieldset>
        <div class="field"><label for="k-desk">Deskripsi <span class="muted">(opsional)</span></label><input class="input" id="k-desk" name="deskripsi" maxlength="255" value="{{ $k['deskripsi'] ?? '' }}"></div>
        <div class="aksi-form"><button class="btn" type="submit">{!! icon('cek') !!} Simpan</button>@if ($editKat)<a class="btn btn-teks" href="{{ route('admin.kategori') }}">Batal</a>@endif</div>
      </form>
    </section>

    <section class="kartu" id="form-topik">
      <div class="kartu-judul"><h2>{!! icon($editTopik ? 'edit' : 'plus') !!} {!! $editTopik ? 'Edit topik' : 'Topik baru' !!}</h2></div>
      <form method="post" data-validasi>
        @csrf<input type="hidden" name="aksi" value="simpan-topik"><input type="hidden" name="id" value="{!! (int) ($t['id'] ?? 0) !!}">
        <div class="field {!! isset($errT['category_id']) ? 'salah' : '' !!}"><label for="t-kat">Mata pelajaran</label>
          <select class="input" id="t-kat" name="category_id" required data-label="Mata pelajaran"><option value="">Pilih</option>@foreach ($kategori as $c)<option value="{!! (int) $c['id'] !!}" {!! (string) ($t['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' !!}>{{ $c['nama'] }}</option>@endforeach</select>{!! err($errT, 'category_id') !!}</div>
        <div class="field {!! isset($errT['nama']) ? 'salah' : '' !!}"><label for="t-nama">Nama topik</label>
          <input class="input" id="t-nama" name="nama" required minlength="2" maxlength="80" data-label="Nama topik" value="{{ $t['nama'] }}" placeholder="mis. Trigonometri"{!! aria_err($errT, 'nama') !!}>{!! err($errT, 'nama') !!}</div>
        <div class="aksi-form"><button class="btn" type="submit">{!! icon('cek') !!} Simpan</button>@if ($editTopik)<a class="btn btn-teks" href="{{ route('admin.kategori') }}">Batal</a>@endif</div>
      </form>
    </section>
  </aside>
</div>
@endsection
