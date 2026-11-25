@php
$tipe = array_filter(explode(',', (string) ($k['daftar_tipe'] ?? '')));
@endphp
<article class="kuis-kartu {!! warna($k['warna']) !!}">
  <div class="kuis-sampul">
    <span class="chip">{{ $k['kategori'] }}</span>
    @if ((int) $k['durasi_menit'] > 0)<span class="chip">{!! icon('jam') !!}{!! (int) $k['durasi_menit'] !!} mnt</span>@endif
    @include('partials.deko')
  </div>
  <div class="kuis-isi">
    <h3><a href="{{ route('kuis.show', $k['id']) }}">{{ $k['judul'] }}</a></h3>
    @if ($k['deskripsi'])<p>{{ $k['deskripsi'] }}</p>@endif
    <div class="kuis-meta">
      <span class="chip chip-garis">{{ label(KESULITAN, $k['kesulitan']) }}</span>
      <span class="chip chip-garis">{{ label(JENJANG, $k['jenjang']) }}</span>
      @if ($k['topik'])<span class="chip chip-garis">{{ $k['topik'] }}</span>@endif
      @if (count($tipe) > 1)<span class="chip chip-garis">Campuran</span>
      @elseif ($tipe)<span class="chip chip-garis">{{ TIPE_SOAL[$tipe[0]] ?? '' }}</span>@endif
      @if (($k['status'] ?? 'terbit') === 'draft')<span class="chip w-oranye">Draft</span>@endif
    </div>
    <div class="kuis-kaki">
      <span>{!! icon('soal') !!}{!! (int) $k['jumlah_soal'] !!} soal</span>
      <span>{!! icon('pengguna') !!}{!! (int) $k['jumlah_percobaan'] !!}× dikerjakan</span>
    </div>
  </div>
</article>
