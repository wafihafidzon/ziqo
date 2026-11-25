@php $menu = $menu ?? ''; @endphp
<!doctype html>
<html lang="id">
<head>@include('partials.head', ['title' => $title ?? ''])</head>
<body>
<a class="sr-only" href="#konten">Lewati ke konten</a>
<header class="navbar">
  <div class="wadah">
    <a class="logo" href="{{ route('home') }}"><img src="{{ asset('assets/logo-ziqo.webp') }}" alt="ZIQO, ke beranda"></a>
    <nav aria-label="Navigasi publik">
      <a href="{{ route('home') }}" class="{!! $menu === 'beranda' ? 'aktif' : '' !!}">Beranda</a>
      <a href="{{ route('jelajah') }}" class="{!! $menu === 'jelajah' ? 'aktif' : '' !!}">Jelajah Kuis</a>
      <a href="{{ route('home') }}#kategori">Kategori</a>
    </nav>
    <div class="aksi">
      @if (auth()->check())
        <a class="btn" href="{{ route('dashboard') }}">{!! icon('home') !!} Dashboard</a>
      @else
        <a class="btn btn-garis" href="{{ route('login') }}">Masuk</a>
        <a class="btn" href="{{ route('register') }}">Daftar gratis</a>
      @endif
    </div>
  </div>
</header>
<main id="konten">
  @yield('konten')
</main>
<footer class="footer">
  <div class="wadah">
    <img src="{{ asset('assets/logo-ziqo.webp') }}" alt="ZIQO">
    <span>&copy; {!! date('Y') !!} ZIQO, Zone of Interactive Quiz &amp; Opportunity. Proyek Pemrograman Web.</span>
  </div>
</footer>
@include('partials.global')
<script src="{{ asset('assets/js/app.js') }}"></script>
@foreach ($scripts ?? [] as $s)<script src="{{ asset($s) }}"></script>@endforeach
</body>
</html>
