@extends('layouts.public')

@section('konten')
@php
$jumlahPeserta = (int) ($stat['pengguna']['peserta'] ?? 0);
$jumlahPengajar = (int) ($stat['pengguna']['pengajar'] ?? 0);
@endphp
<section class="hero">
  <div class="wadah">
    <div>
      <span class="chip w-kuning" style="margin-bottom:1rem">{!! icon('bintang') !!} Zone of Interactive Quiz &amp; Opportunity</span>
      <h1>Belajar jadi <span class="sorot">seru</span>,<br>satu kuis setiap hari.</h1>
      <p class="lead">ZIQO mengubah latihan soal menjadi permainan: pilih mata pelajaran, jawab tantangan, kumpulkan XP dan badge, lalu lihat perkembanganmu dari waktu ke waktu.</p>
      <div class="cta">
        @if (auth()->check())
          <a class="btn btn-besar" href="{{ route('jelajah') }}">{!! icon('jelajah') !!} Mulai main kuis</a>
          <a class="btn btn-besar btn-putih" href="{{ route('dashboard') }}">Ke dashboard</a>
        @else
          <a class="btn btn-besar" href="{{ route('register') }}">{!! icon('kilat') !!} Mulai belajar gratis</a>
          <a class="btn btn-besar btn-putih" href="{{ route('jelajah') }}">{!! icon('jelajah') !!} Jelajahi kuis</a>
        @endif
      </div>
      <div class="hero-bukti">
        <div><b>{!! angka($stat['kuis']) !!}</b><span>kuis siap dimainkan</span></div>
        <div><b>{!! angka($stat['soal']) !!}</b><span>soal dalam 5 tipe</span></div>
        <div><b>{!! angka($stat['kategori']) !!}</b><span>mata pelajaran</span></div>
        <div><b>{!! angka($stat['percobaan']) !!}</b><span>kuis telah diselesaikan</span></div>
      </div>
    </div>
    <div class="hero-visual" aria-hidden="true">
      <div class="blob"></div>
      <span class="tanya" style="left:16%;top:4%;font-size:4.5rem">?</span>
      <span class="tanya" style="right:10%;top:10%;font-size:3rem;color:#fff;text-shadow:0 4px 0 #b3a9e6">?</span>
      <span class="tanya" style="right:20%;bottom:6%;font-size:3.5rem">?</span>
      <img class="maskot" src="{{ asset('assets/maskot.webp') }}" alt="">
      <div class="kartu-mengambang km-1"><span class="lencana-mini w-hijau">{!! icon('langkah') !!}</span> First Step</div>
      <div class="kartu-mengambang km-2"><span class="lencana-mini w-kuning">{!! icon('bintang') !!}</span> Perfect Score!</div>
      <div class="kartu-mengambang km-3"><span class="lencana-mini w-ungu">{!! icon('kilat') !!}</span> +40 XP</div>
    </div>
  </div>
</section>

<section class="seksi" style="padding-top:1rem">
  <div class="wadah">
    <div class="hub">
      <a class="w-ungu" href="{{ route('jelajah') }}"><span class="hub-ikon">{!! icon('kilat') !!}</span><b>Main</b><small>Pilih kuis dan langsung kerjakan</small></a>
      <a class="w-kuning" href="{{ route('jelajah', kueri(['sort' => 'populer'])) }}"><span class="hub-ikon">{!! icon('trofi') !!}</span><b>Peringkat</b><small>Kuis terpopuler dan skor terbaik</small></a>
      <a class="w-hijau" href="{{ route('pencapaian') }}"><span class="hub-ikon">{!! icon('medali') !!}</span><b>Pencapaian</b><small>Kumpulkan badge dari belajarmu</small></a>
      <a class="w-biru" href="{{ route('dashboard') }}"><span class="hub-ikon">{!! icon('chart') !!}</span><b>Statistik</b><small>Pantau perkembangan nilaimu</small></a>
    </div>
  </div>
</section>

<section class="seksi" id="kategori">
  <div class="wadah">
    <div class="seksi-judul">
      <span class="eyebrow">{!! icon('kategori') !!} Kategori</span>
      <h2>Pilih mata pelajaran favoritmu</h2>
      <p>Setiap kuis dikelompokkan berdasarkan mata pelajaran, topik, jenjang, dan tingkat kesulitan.</p>
    </div>
    @if ($kategori->isNotEmpty())
      <div class="kategori-grid">
        @foreach ($kategori as $c)
          <a class="kategori-kartu {!! warna($c['warna']) !!}" href="{{ route('jelajah', kueri(['kategori' => $c['slug']])) }}">
            <span class="huruf">{{ mb_substr($c['nama'], 0, 1) }}</span>
            <b>{{ $c['nama'] }}</b>
            <small>{!! (int) $c['jumlah_kuis'] !!} kuis</small>
          </a>
        @endforeach
      </div>
    @else
      @include('partials.kosong', ['judul' => 'Kategori belum tersedia', 'pesan' => 'Kuis akan muncul di sini setelah pengajar menerbitkannya.'])
    @endif
  </div>
</section>

<section class="seksi">
  <div class="wadah">
    <div class="seksi-judul">
      <span class="eyebrow">{!! icon('api') !!} Populer</span>
      <h2>Kuis yang sedang ramai</h2>
      <p>Diurutkan dari jumlah pengerjaan terbanyak oleh pengguna ZIQO.</p>
    </div>
    @if ($populer->isNotEmpty())
      <div class="grid-kuis">
        @foreach ($populer as $k)
    @include('partials.kuis-kartu', ['k' => $k])
  @endforeach
      </div>
      <p class="teks-tengah mt-2"><a class="btn btn-putih" href="{{ route('jelajah') }}">Lihat semua kuis {!! icon('panah-kanan') !!}</a></p>
    @else
      @include('partials.kosong', ['judul' => 'Belum ada kuis terbit'])
    @endif
  </div>
</section>

<section class="seksi">
  <div class="wadah">
    <div class="seksi-judul">
      <span class="eyebrow">{!! icon('bintang') !!} Keunggulan</span>
      <h2>Kenapa belajar di ZIQO?</h2>
    </div>
    <div class="fitur">
      <div class="kartu w-ungu"><div class="stat-ikon">{!! icon('soal') !!}</div><h3>5 tipe soal</h3><p>Pilihan ganda, pilihan ganda kompleks, benar/salah, isian singkat, dan esai yang dinilai pengajar.</p></div>
      <div class="kartu w-kuning"><div class="stat-ikon">{!! icon('kilat') !!}</div><h3>Umpan balik langsung</h3><p>Pada kuis latihan, kamu langsung tahu benar atau salah beserta pembahasannya.</p></div>
      <div class="kartu w-hijau"><div class="stat-ikon">{!! icon('chart') !!}</div><h3>Statistik nyata</h3><p>Grafik perkembangan dan nilai per mata pelajaran dihitung dari hasil pengerjaanmu sendiri.</p></div>
      <div class="kartu w-pink"><div class="stat-ikon">{!! icon('medali') !!}</div><h3>Badge &amp; XP</h3><p>Raih {!! $badge->isNotEmpty() ? 'badge seperti ' . e($badge->first()['nama']) . ' dan ' . e($badge->last()['nama']) : 'badge' !!} dari pencapaian belajarmu.</p></div>
    </div>
  </div>
</section>

<section class="seksi">
  <div class="wadah">
    <div class="ajakan">
      @include('partials.deko', ['varian' => 'gelap'])
      <div style="position:relative;z-index:1">
        <h2>Siap naik level?</h2>
        <p>Bergabung bersama {!! angka($jumlahPeserta) !!} peserta dan {!! angka($jumlahPengajar) !!} pengajar di ZIQO. Gratis, cukup dengan email.</p>
        <div class="cta mt-1" style="display:flex;gap:.75rem;flex-wrap:wrap">
          @if (auth()->check())
            <a class="btn btn-kuning btn-besar" href="{{ route('jelajah') }}">Cari kuis berikutnya</a>
          @else
            <a class="btn btn-kuning btn-besar" href="{{ route('register') }}">Buat akun sekarang</a>
            <a class="btn btn-garis btn-besar" style="--fg:#fff;box-shadow:inset 0 0 0 2px rgba(255,255,255,.4)" href="{{ route('login') }}">Saya sudah punya akun</a>
          @endif
        </div>
      </div>
      <img src="{{ asset('assets/maskot.webp') }}" alt="">
    </div>
  </div>
</section>
@endsection
