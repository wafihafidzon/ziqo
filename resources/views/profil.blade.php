@extends('layouts.app')

@section('konten')
@php
$val = fn($k) => old('aksi') === 'info' ? old($k) : ($user[$k] ?? '');
@endphp
<div class="kepala-halaman"><div><h1>Profil saya</h1><p>Kelola informasi akun, foto profil, dan keamanan.</p></div></div>

<div class="grid grid-profil">
  <section class="kartu profil-kartu">
    <div class="profil-sampul"></div>
    <div class="avatar-wrap">
      <img class="avatar avatar-besar" src="{{ avatar_url($user) }}" alt="Foto profil {{ $user['nama'] }}">
      <a class="avatar-ganti" href="{{ route('profil', kueri(['tab' => 'avatar'])) }}" aria-label="Ganti foto profil">{!! icon('kamera') !!}</a>
    </div>
    <h2>{{ $user['nama'] }}</h2>
    <p class="muted kecil mb-1">{{ $user['email'] }}</p>
    <div class="baris" style="justify-content:center">
      <span class="chip">{{ ROLE[$user['role']] }}</span>
      @if ($user['jenjang'])<span class="chip chip-garis">{{ JENJANG[$user['jenjang']] ?? '' }}</span>@endif
    </div>
    @if ($user['bio'])<p class="kecil muted mt-1" style="padding:0 1.25rem">{{ $user['bio'] }}</p>@endif
    <div class="grid grid-2 mt-1" style="padding:0 1.25rem;gap:.6rem">
      <div class="info" style="justify-content:center"><div><small>Kuis selesai</small><b>{!! (int) $stat['total'] !!}</b></div></div>
      <div class="info" style="justify-content:center"><div><small>Badge</small><b>{!! (int) $stat['badge'] !!}</b></div></div>
    </div>
    <p class="kecil muted mt-1 mb-0">Bergabung {{ tanggal($user['created_at']) }}</p>
  </section>

  <section class="kartu">
    <nav class="tabs" aria-label="Pengaturan profil">
      <a href="?tab=info" class="{!! $tab === 'info' ? 'aktif' : '' !!}">Informasi</a>
      <a href="?tab=avatar" class="{!! $tab === 'avatar' ? 'aktif' : '' !!}">Foto profil</a>
      <a href="?tab=password" class="{!! $tab === 'password' ? 'aktif' : '' !!}">Keamanan</a>
    </nav>

    @if ($tab === 'info')
      <form method="post" data-validasi>
        @csrf<input type="hidden" name="aksi" value="info">
        <div class="baris-form">
          <div class="field {!! isset($errors['nama']) ? 'salah' : '' !!}">
            <label for="nama">Nama lengkap</label>
            <input class="input" id="nama" name="nama" required minlength="3" maxlength="100" data-label="Nama" value="{{ $val('nama') }}"{!! aria_err($errors, 'nama') !!}>
            {!! err($errors, 'nama') !!}
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input class="input" id="email" type="email" value="{{ $user['email'] }}" readonly aria-describedby="email-bantuan">
            <div class="bantuan" id="email-bantuan">Email dipakai untuk masuk dan tidak dapat diubah.</div>
          </div>
        </div>
        <div class="baris-form">
          <div class="field {!! isset($errors['telepon']) ? 'salah' : '' !!}">
            <label for="telepon">No. telepon <span class="muted">(opsional)</span></label>
            <input class="input" id="telepon" name="telepon" type="tel" inputmode="numeric" pattern="\+?[0-9]{8,15}" title="Hanya angka, 8–15 digit" data-label="No. telepon" value="{{ $val('telepon') }}" placeholder="08xxxxxxxxxx"{!! aria_err($errors, 'telepon') !!}>
            {!! err($errors, 'telepon') !!}
          </div>
          <div class="field">
            <label for="jenjang">Jenjang pendidikan</label>
            <select class="input" id="jenjang" name="jenjang">
              <option value="">Pilih jenjang</option>
              @foreach (JENJANG as $k => $v)<option value="{!! $k !!}" {!! $val('jenjang') === $k ? 'selected' : '' !!}>{{ $v }}</option>@endforeach
            </select>
            <div class="bantuan">Dipakai untuk rekomendasi kuis.</div>
          </div>
        </div>
        <div class="field {!! isset($errors['bio']) ? 'salah' : '' !!}">
          <label for="bio">Bio singkat <span class="muted">(opsional)</span></label>
          <textarea class="input" id="bio" name="bio" maxlength="300" rows="3" placeholder="Ceritakan sedikit tentang dirimu">{{ $val('bio') }}</textarea>
          {!! err($errors, 'bio') !!}
        </div>
        <button class="btn" type="submit">{!! icon('cek') !!} Simpan perubahan</button>
      </form>

    @elseif ($tab === 'avatar')
      <form method="post" enctype="multipart/form-data">
        @csrf<input type="hidden" name="aksi" value="avatar">
        <input type="hidden" name="MAX_FILE_SIZE" value="{!! \App\Support\Avatar::MAKS_BYTE !!}">
        <div class="field {!! isset($errors['avatar']) ? 'salah' : '' !!}">
          <label for="avatar">Unggah foto baru</label>
          <div class="unggah-zona">
            <img class="avatar" id="pratinjau-avatar" src="{{ avatar_url($user) }}" alt="Pratinjau foto">
            <div style="flex:1;min-width:0">
              <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required data-pratinjau="pratinjau-avatar" data-info="info-avatar"{!! aria_err($errors, 'avatar') !!}>
              <p class="kecil muted mb-0 mt-1" id="info-avatar">JPG, PNG, atau WEBP · maksimal 2 MB · minimal 64×64 px. Gambar akan dipotong persegi.</p>
            </div>
          </div>
          {!! err($errors, 'avatar') !!}
        </div>
        <button class="btn" type="submit">{!! icon('unggah') !!} Simpan foto</button>
      </form>
      @if ($user['avatar'])
        <form method="post" class="mt-1" data-confirm="Foto profil akan dihapus dan diganti maskot ZIQO." data-confirm-judul="Hapus foto profil?" data-confirm-ok="Hapus" data-bahaya>
          @csrf<input type="hidden" name="aksi" value="hapus-avatar">
          <button class="btn btn-teks" type="submit" style="--fg:var(--merah)">{!! icon('sampah') !!} Hapus foto</button>
        </form>
      @endif

    @else
      <form method="post" data-validasi autocomplete="off">
        @csrf<input type="hidden" name="aksi" value="password">
        <div class="field {!! isset($errors['password_lama']) ? 'salah' : '' !!}">
          <label for="password_lama">Password saat ini</label>
          <div class="input-ikon">{!! icon('kunci') !!}<input class="input" id="password_lama" name="password_lama" type="password" required autocomplete="current-password" data-label="Password saat ini"{!! aria_err($errors, 'password_lama') !!}>
            <button class="toggle-mata" type="button" aria-label="Tampilkan password">{!! icon('mata') !!}</button></div>
          {!! err($errors, 'password_lama') !!}
        </div>
        <div class="baris-form">
          <div class="field {!! isset($errors['password_baru']) ? 'salah' : '' !!}">
            <label for="password_baru">Password baru</label>
            <div class="input-ikon">{!! icon('kunci') !!}<input class="input" id="password_baru" name="password_baru" type="password" required minlength="8" maxlength="72" autocomplete="new-password" data-label="Password baru"{!! aria_err($errors, 'password_baru') !!}>
              <button class="toggle-mata" type="button" aria-label="Tampilkan password">{!! icon('mata') !!}</button></div>
            {!! err($errors, 'password_baru') !!}
          </div>
          <div class="field {!! isset($errors['konfirmasi']) ? 'salah' : '' !!}">
            <label for="konfirmasi">Konfirmasi password baru</label>
            <div class="input-ikon">{!! icon('kunci') !!}<input class="input" id="konfirmasi" name="konfirmasi" type="password" required data-sama="password_baru" autocomplete="new-password" data-label="Konfirmasi password"{!! aria_err($errors, 'konfirmasi') !!}></div>
            {!! err($errors, 'konfirmasi') !!}
          </div>
        </div>
        <button class="btn" type="submit">{!! icon('perisai') !!} Ganti password</button>
      </form>
    @endif
  </section>
</div>
@endsection
