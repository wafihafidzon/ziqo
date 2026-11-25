@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('konten')
<div class="halaman-error">
  <div class="kode">{!! (int) $kode !!}</div>
  <img src="{{ asset('assets/maskot.webp') }}" alt="">
  <h1 class="judul-font" style="color:var(--ungu-800)">{{ $judul }}</h1>
  <p class="muted">{{ $pesan }}</p>
  <div class="baris" style="justify-content:center">
    <a class="btn" href="{{ auth()->check() ? route('dashboard') : route('home') }}">{!! icon('home') !!} Kembali ke beranda</a>
    <a class="btn btn-putih" href="{{ route('jelajah') }}">Jelajah kuis</a>
  </div>
</div>
@endsection
