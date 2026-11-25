@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('konten')
@php $publik = !auth()->check(); @endphp
@if ($publik)<div class="wadah" style="padding:2rem 0 3rem">@endif

<div class="kepala-halaman">
  <div>
    <h1>Jelajah kuis</h1>
    <p>Gabungkan filter mata pelajaran, jenis soal, kesulitan, dan jenjang untuk menemukan kuis yang pas.</p>
  </div>
</div>

<nav class="scroll-kategori" aria-label="Kategori mata pelajaran">
  <a href="{{ route('jelajah', kueri(array_diff_key($f, ['kategori' => 1, 'topik' => 1]))) }}" class="{!! !$kategoriAktif ? 'aktif' : '' !!}"><span class="huruf w-ungu">{!! icon('jelajah') !!}</span>Semua</a>
  @php foreach ($kategori as $c): if (!(int) $c['jumlah_kuis']) continue; @endphp
    <a href="{{ route('jelajah', kueri(array_merge(array_diff_key($f, ['topik' => 1]), ['kategori' => $c['slug']]))) }}" class="{!! warna($c['warna']) !!} {!! $kategoriAktif && $kategoriAktif['id'] == $c['id'] ? 'aktif' : '' !!}">
      <span class="huruf">{{ mb_substr($c['nama'], 0, 1) }}</span>{{ $c['nama'] }} <small class="muted">({!! (int) $c['jumlah_kuis'] !!})</small>
    </a>
  @endforeach
</nav>

<div class="grid grid-jelajah">
  <div>
    <div class="baris-antara mb-1">
      <p class="mb-0 tebal">{!! (int) $pg['total'] !!} kuis ditemukan</p>
      <div class="tabs mb-0" role="tablist" aria-label="Urutkan">
        @php foreach (\App\Models\Quiz::SORT as $k => $label): if ($k === 'rekomendasi' && $publik) continue; @endphp
          <a href="{{ route('jelajah', kueri(array_merge($f, ['sort' => $k]))) }}" class="{!! $f['sort'] === $k ? 'aktif' : '' !!}" {!! $f['sort'] === $k ? 'aria-current="true"' : '' !!}>{{ $label }}</a>
        @endforeach
      </div>
    </div>
    @if ($aktif)
      <div class="filter-aktif">
        <span class="kecil muted tebal">Filter:</span>
        @foreach ($aktif as [$label, $href])
          <a class="chip" href="{{ $href }}" title="Hapus filter">{{ $label }} {!! icon('silang') !!}</a>
        @endforeach
        <a class="kecil tebal" href="{{ route('jelajah') }}">Reset semua</a>
      </div>
    @endif

    @if ($hasil->isNotEmpty())
      <div class="grid-kuis">@foreach ($hasil as $k)
    @include('partials.kuis-kartu', ['k' => $k])
  @endforeach</div>
      @include('partials.paginasi', ['pg' => $pg])
    @else
      <div class="kartu">@include('partials.kosong', ['judul' => 'Tidak ada kuis yang cocok', 'pesan' => 'Coba kurangi filter atau gunakan kata kunci lain.', 'aksi' => ['Reset filter', route('jelajah')]])</div>
    @endif
  </div>

  <aside class="kartu filter-panel" aria-label="Filter kuis">
    <form method="get" action="{{ route('jelajah') }}" data-auto-kirim>
      <input type="hidden" name="sort" value="{{ $f['sort'] }}">
      <details class="filter-lipat" open data-lipat-mobile="{!! $aktif ? '0' : '1' !!}">
      <summary class="kartu-judul"><h2>{!! icon('filter') !!} Filter{!! $aktif ? ' (' . count($aktif) . ')' : '' !!}</h2><span class="filter-panah">{!! icon('chevron-bawah') !!}</span></summary>
      <div class="field">
        <label class="label" for="f-q">Kata kunci</label>
        <input class="input" id="f-q" type="search" name="q" value="{{ $f['q'] }}" placeholder="mis. aljabar">
      </div>
      <div class="field">
        <label class="label" for="f-kategori">Mata pelajaran</label>
        <select class="input" id="f-kategori" name="kategori">
          <option value="">Semua mata pelajaran</option>
          @foreach ($kategori as $c)<option value="{{ $c['slug'] }}" {!! $f['kategori'] === $c['slug'] ? 'selected' : '' !!}>{{ $c['nama'] }}</option>@endforeach
        </select>
      </div>
      @if ($topik->isNotEmpty())
      <div class="field">
        <label class="label" for="f-topik">Topik</label>
        <select class="input" id="f-topik" name="topik">
          <option value="">Semua topik</option>
          @foreach ($topik as $t)<option value="{!! (int) $t['id'] !!}" {!! (int) $f['topik'] === (int) $t['id'] ? 'selected' : '' !!}>{{ $t['nama'] }}</option>@endforeach
        </select>
      </div>
      @endif
      <div class="field">
        <label class="label" for="f-tipe">Jenis soal</label>
        <select class="input" id="f-tipe" name="tipe">
          <option value="">Semua jenis</option>
          @foreach (TIPE_SOAL as $k => $v)<option value="{!! $k !!}" {!! $f['tipe'] === $k ? 'selected' : '' !!}>{{ $v }}</option>@endforeach
        </select>
      </div>
      <fieldset class="field" style="border:0;padding:0;margin:0 0 .9rem">
        <legend class="label">Tingkat kesulitan</legend>
        <div class="chip-pilih">
          <label><input type="radio" name="kesulitan" value="" {!! !$f['kesulitan'] ? 'checked' : '' !!}><span>Semua</span></label>
          @foreach (KESULITAN as $k => $v)<label><input type="radio" name="kesulitan" value="{!! $k !!}" {!! $f['kesulitan'] === $k ? 'checked' : '' !!}><span>{{ $v }}</span></label>@endforeach
        </div>
      </fieldset>
      <fieldset class="field" style="border:0;padding:0;margin:0 0 .9rem">
        <legend class="label">Jenjang</legend>
        <div class="chip-pilih">
          <label><input type="radio" name="jenjang" value="" {!! !$f['jenjang'] ? 'checked' : '' !!}><span>Semua</span></label>
          @foreach (JENJANG as $k => $v)<label><input type="radio" name="jenjang" value="{!! $k !!}" {!! $f['jenjang'] === $k ? 'checked' : '' !!}><span>{{ $v }}</span></label>@endforeach
        </div>
      </fieldset>
      <button class="btn btn-blok" type="submit">{!! icon('cari') !!} Terapkan</button>
      </details>
    </form>
  </aside>
</div>

@if ($publik)</div>@endif
@endsection
