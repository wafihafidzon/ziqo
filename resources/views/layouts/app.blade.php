@php
$u = auth()->user();
$menu = $menu ?? '';
$guru = $u->hasRole('pengajar', 'admin');
$admin = $u->hasRole('admin');
$nav = [
    ['', [
        ['dashboard', route('dashboard'), 'Dashboard', 'home'],
        ['jelajah', route('jelajah'), 'Jelajah Kuis', 'jelajah'],
        ...($guru ? [] : [['room', route('room'), 'Masuk Room', 'kunci']]),
        ...($guru ? [] : [
            ['riwayat', route('riwayat'), 'Riwayat', 'riwayat'],
            ['pencapaian', route('pencapaian'), 'Pencapaian', 'trofi'],
        ]),
        ['profil', route('profil'), 'Profil', 'profil'],
    ]],
];
if ($guru) {
    $esaiMenunggu = min(99, app(\App\Services\StatsService::class)->pendingEssayCount($admin ? null : $u->id));
}
// Admin hanya supervisor: tidak punya menu Mengajar; halaman kelola kuis milik pengajar disorot sebagai Pantau Kuis.
if ($admin && $menu === 'kuis-saya') $menu = 'admin-kuis';
if ($guru && !$admin) {
    $nav[] = ['Mengajar', [
        ['kuis-saya', route('pengajar.kuis'), 'Kuis Saya', 'quiz'],
        ['room-pantau', route('pengajar.rooms'), 'Room Saya', 'kunci'],
        ['penilaian', route('pengajar.penilaian'), 'Penilaian Esai', 'pena', $esaiMenunggu],
    ]];
}
if ($admin) {
    $nav[] = ['Administrator', [
        ['admin-pengguna', route('admin.pengguna'), 'Pengguna', 'pengguna'],
        ['admin-kategori', route('admin.kategori'), 'Kategori & Topik', 'kategori'],
        ['admin-kuis', route('admin.kuis'), 'Pantau Kuis', 'perisai'],
        ['room-pantau', route('pengajar.rooms'), 'Pantau Room', 'kunci'],
        ['admin-esai', route('pengajar.penilaian'), 'Penilaian Esai (cadangan)', 'pena', $esaiMenunggu],
    ]];
}
@endphp
<!doctype html>
<html lang="id">
<head>@include('partials.head', ['title' => $title ?? ''])</head>
<body>
<a class="sr-only" href="#konten">Lewati ke konten</a>
<div class="app">
  <aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
    <a class="sidebar-logo" href="{{ route('dashboard') }}"><img src="{{ asset('assets/logo-ziqo.webp') }}" alt="ZIQO"></a>
    <nav class="nav">
      @foreach ($nav as [$grup, $items])
        @if ($grup)<div class="nav-grup">{{ $grup }}</div>@endif
        @foreach ($items as $it)
          <a href="{{ $it[1] }}" class="{!! $menu === $it[0] ? 'aktif' : '' !!}" {!! $menu === $it[0] ? 'aria-current="page"' : '' !!}>
            {!! icon($it[3]) !!}<span>{{ $it[2] }}</span>
            @if (!empty($it[4]))<span class="angka" title="{!! (int) $it[4] !!} menunggu">{!! (int) $it[4] !!}</span>@endif
          </a>
        @endforeach
      @endforeach
    </nav>
    <div class="sidebar-maskot" aria-hidden="true"><img src="{{ asset('assets/maskot.webp') }}" alt=""></div>
  </aside>

  <div class="utama">
    <header class="topbar">
      <button class="btn btn-putih btn-ikon menu-btn" type="button" data-buka-sidebar aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu">{!! icon('menu') !!}</button>
      <form class="cari-global" action="{{ route('jelajah') }}" method="get" role="search">
        {!! icon('cari') !!}
        <label class="sr-only" for="cari-global">Cari kuis</label>
        <input id="cari-global" type="search" name="q" placeholder="Cari kuis, topik, atau materi..." value="{{ request('q', '') }}">
      </form>
      <div class="user-menu">
        <button class="user-pil" type="button" data-toggle-menu aria-expanded="false" aria-controls="user-dropdown">
          <img class="avatar" src="{{ avatar_url($u) }}" alt="">
          <span><b>{{ $u['nama'] }}</b><small>{{ ROLE[$u['role']] }}</small></span>
          {!! icon('chevron-bawah') !!}
        </button>
        <div class="user-dropdown" id="user-dropdown" hidden>
          <a href="{{ route('profil') }}">{!! icon('profil') !!} Profil saya</a>
          @unless ($guru)
          <a href="{{ route('pencapaian') }}">{!! icon('trofi') !!} Pencapaian</a>
          <a href="{{ route('riwayat') }}">{!! icon('riwayat') !!} Riwayat</a>
          @endunless
          <hr>
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="keluar">{!! icon('logout') !!} Keluar</button>
          </form>
        </div>
      </div>
    </header>
    <main id="konten">
      @yield('konten')
    </main>
  </div>
</div>
@include('partials.global')
<script src="{{ asset('assets/js/app.js') }}"></script>
@foreach ($scripts ?? [] as $s)<script src="{{ asset($s) }}"></script>@endforeach
</body>
</html>
