@extends('layouts.bare')

@section('konten')
<div class="auth">
  <section class="auth-brand">
    <a class="logo" href="{{ route('home') }}"><img src="{{ asset('assets/logo-ziqo.webp') }}" alt="ZIQO, ke beranda"></a>
    <p class="sapa">Let&rsquo;s learn<br>together!</p>
    <img class="maskot" src="{{ asset('assets/maskot.webp') }}" alt="">
  </section>
  <div class="auth-panel-wrap">
    <div class="auth-panel">
      <h1>Welcome back!</h1>
      <p class="deskripsi">Masuk dan lanjutkan petualangan belajarmu.</p>
      @if ($error)<div class="alert alert-error" role="alert">{!! icon('peringatan') !!}<span>{{ $error }}</span></div>@endif
      <form method="post" action="{{ route('login') }}" data-validasi>
        @csrf
        <div class="field">
          <label for="email">Email</label>
          <div class="input-ikon">{!! icon('mail') !!}
            <input class="input" id="email" name="email" type="email" required autocomplete="email" data-label="Email"
                   value="{{ old('email', $emailIngat) }}" placeholder="nama@email.com">
          </div>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="input-ikon">{!! icon('kunci') !!}
            <input class="input" id="password" name="password" type="password" required autocomplete="current-password" data-label="Password" placeholder="Password kamu">
            <button class="toggle-mata" type="button" aria-label="Tampilkan password" aria-pressed="false">{!! icon('mata') !!}</button>
          </div>
        </div>
        <div class="baris-antara mb-1">
          <label class="cek"><input type="checkbox" name="ingat" value="1" {!! $emailIngat !== '' ? 'checked' : '' !!}> Ingat email saya</label>
        </div>
        <button class="btn btn-besar btn-blok" type="submit">{!! icon('login') !!} Masuk</button>
      </form>
      <p class="pindah">Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a></p>
      <div class="akun-demo">
        <b>Akun demo untuk presentasi</b>
        <ul>
          <li>Administrator: admin@ziqo.id / admin123</li>
          <li>Pengajar: guru@ziqo.id / guru1234</li>
          <li>Peserta: demo@ziqo.id / demo1234</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
