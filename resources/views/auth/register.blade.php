@extends('layouts.bare')

@section('konten')
<div class="auth">
  <section class="auth-brand">
    <a class="logo" href="{{ route('home') }}"><img src="{{ asset('assets/logo-ziqo.webp') }}" alt="ZIQO, ke beranda"></a>
    <p class="sapa">Kumpulkan XP,<br>raih badge pertamamu!</p>
    <img class="maskot" src="{{ asset('assets/maskot.webp') }}" alt="">
  </section>
  <div class="auth-panel-wrap">
    <div class="auth-panel">
      <h1>Daftar akun</h1>
      <p class="deskripsi">Gratis. Akun baru terdaftar sebagai Peserta.</p>
      @if ($errors)<div class="alert alert-error" role="alert">{!! icon('peringatan') !!}<span>Periksa kembali isian yang ditandai.</span></div>@endif
      <form method="post" action="{{ route('register') }}" data-validasi>
        @csrf
        <div class="field {!! isset($errors['nama']) ? 'salah' : '' !!}">
          <label for="nama">Nama lengkap</label>
          <div class="input-ikon">{!! icon('user') !!}
            <input class="input" id="nama" name="nama" required minlength="3" maxlength="100" autocomplete="name" data-label="Nama" value="{{ old('nama') }}" placeholder="Nama lengkap"{!! aria_err($errors, 'nama') !!}>
          </div>
          {!! err($errors, 'nama') !!}
        </div>
        <div class="field {!! isset($errors['email']) ? 'salah' : '' !!}">
          <label for="email">Email</label>
          <div class="input-ikon">{!! icon('mail') !!}
            <input class="input" id="email" name="email" type="email" required maxlength="190" autocomplete="email" data-label="Email" value="{{ old('email') }}" placeholder="nama@email.com"{!! aria_err($errors, 'email') !!}>
          </div>
          {!! err($errors, 'email') !!}
        </div>
        <div class="field {!! isset($errors['password']) ? 'salah' : '' !!}">
          <label for="password">Password</label>
          <div class="input-ikon">{!! icon('kunci') !!}
            <input class="input" id="password" name="password" type="password" required minlength="8" maxlength="72" autocomplete="new-password" data-label="Password" placeholder="Minimal 8 karakter"{!! aria_err($errors, 'password') !!}>
            <button class="toggle-mata" type="button" aria-label="Tampilkan password" aria-pressed="false">{!! icon('mata') !!}</button>
          </div>
          {!! err($errors, 'password') !!}
        </div>
        <div class="field {!! isset($errors['konfirmasi']) ? 'salah' : '' !!}">
          <label for="konfirmasi">Konfirmasi password</label>
          <div class="input-ikon">{!! icon('kunci') !!}
            <input class="input" id="konfirmasi" name="konfirmasi" type="password" required data-sama="password" autocomplete="new-password" data-label="Konfirmasi password" placeholder="Ulangi password"{!! aria_err($errors, 'konfirmasi') !!}>
            <button class="toggle-mata" type="button" aria-label="Tampilkan password" aria-pressed="false">{!! icon('mata') !!}</button>
          </div>
          {!! err($errors, 'konfirmasi') !!}
        </div>
        <button class="btn btn-besar btn-blok" type="submit">{!! icon('kilat') !!} Daftar sekarang</button>
      </form>
      <p class="pindah">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
    </div>
  </div>
</div>
@endsection
