@extends('layouts.app')

@section('konten')
@php $diri = (int) $u['id'] === (int) $admin['id']; $lindung = $u['role'] === 'admin'; @endphp
<div class="remah"><a href="{{ route('admin.pengguna') }}">Pengguna</a> {!! icon('chevron') !!} <span>{{ $u['nama'] }}</span></div>

<div class="grid grid-profil">
  <section class="kartu profil-kartu">
    <div class="profil-sampul"></div>
    <div class="avatar-wrap"><img class="avatar avatar-besar" src="{{ avatar_url($u) }}" alt=""></div>
    <h2>{{ $u['nama'] }}</h2>
    <p class="muted kecil">{{ $u['email'] }}</p>
    <div class="baris" style="justify-content:center"><span class="chip">{{ ROLE[$u['role']] }}</span><span class="status status-{{ $u['status'] }}">{!! $u['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' !!}</span></div>
    <div style="padding:1rem 1.25rem 0;text-align:left" class="tumpuk">
      <div class="info">{!! icon('mail') !!}<div><small>Telepon</small><b>{{ $u['telepon'] ?: '–' }}</b></div></div>
      <div class="info">{!! icon('buku') !!}<div><small>Jenjang</small><b>{{ $u['jenjang'] ? JENJANG[$u['jenjang']] : '–' }}</b></div></div>
      <div class="info">{!! icon('jam') !!}<div><small>Bergabung</small><b>{{ tanggal($u['created_at'], true) }}</b></div></div>
    </div>
  </section>

  <div class="tumpuk">
    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('perisai') !!} Hak akses</h2></div>
      @if ($lindung || $diri)
        <div class="alert alert-info mb-0">{!! icon('info') !!}<span>{!! $diri ? 'Ini akunmu sendiri. Role dan status akunmu tidak dapat diubah dari sini.' : 'Akun administrator dilindungi: role dan statusnya tidak dapat diubah melalui aplikasi.' !!}</span></div>
      @else
        <div class="grid grid-2">
          <form method="post" action="{{ route('admin.pengguna') }}">
            @csrf<input type="hidden" name="id" value="{!! (int) $u['id'] !!}"><input type="hidden" name="aksi" value="role"><input type="hidden" name="kembali" value="detail">
            <div class="field"><label for="role">Role</label>
              <select class="input" id="role" name="role"><option value="peserta" {!! $u['role'] === 'peserta' ? 'selected' : '' !!}>Peserta</option><option value="pengajar" {!! $u['role'] === 'pengajar' ? 'selected' : '' !!}>Pengajar</option></select>
              <div class="bantuan">Pengajar dapat membuat kuis dan menilai esai.</div></div>
            <button class="btn btn-kecil" type="submit">Simpan role</button>
          </form>
          <form method="post" action="{{ route('admin.pengguna') }}" {!! $u['status'] === 'aktif' ? 'data-confirm="Pengguna akan langsung keluar dan tidak dapat masuk kembali." data-confirm-judul="Nonaktifkan akun?" data-confirm-ok="Nonaktifkan" data-bahaya' : '' !!}>
            @csrf<input type="hidden" name="id" value="{!! (int) $u['id'] !!}"><input type="hidden" name="aksi" value="status"><input type="hidden" name="kembali" value="detail">
            <div class="field"><span class="label">Status akun</span>
              <p class="mb-1"><span class="status status-{{ $u['status'] }}">{!! $u['status'] === 'aktif' ? 'Aktif: dapat masuk dan memakai semua fitur' : 'Nonaktif: tidak dapat masuk' !!}</span></p></div>
            @if ($u['status'] === 'aktif')
              <button class="btn btn-kecil btn-merah" name="status" value="nonaktif" type="submit">{!! icon('power') !!} Nonaktifkan</button>
            @else
              <button class="btn btn-kecil btn-hijau" name="status" value="aktif" type="submit">{!! icon('power') !!} Aktifkan</button>
            @endif
          </form>
        </div>
      @endif
    </section>

    <section class="grid grid-4">
      <div class="stat w-ungu"><span class="stat-nilai">{!! (int) $stat['total'] !!}</span><span class="stat-label">Kuis selesai</span></div>
      <div class="stat w-biru"><span class="stat-nilai">{!! $stat['rata'] !== null ? angka($stat['rata'], 1) : '–' !!}</span><span class="stat-label">Rata-rata</span></div>
      <div class="stat w-kuning"><span class="stat-nilai">{!! angka($stat['xp']) !!}</span><span class="stat-label">XP</span></div>
      <div class="stat w-pink"><span class="stat-nilai">{!! count($badges) !!}</span><span class="stat-label">Badge</span></div>
    </section>

    @if ($kuis->isNotEmpty())
    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('quiz') !!} Kuis yang dibuat</h2></div>
      <ul class="aktivitas">
        @foreach ($kuis as $k)
          <li class="{!! warna($k['warna']) !!}"><span class="bulat">{!! icon('quiz') !!}</span><div class="isi"><b><a href="{{ route('kuis.show', $k['id']) }}" style="color:inherit">{{ $k['judul'] }}</a></b><small>{{ $k['kategori'] }} · {!! (int) $k['jumlah_soal'] !!} soal · {!! (int) $k['jumlah_percobaan'] !!}× dikerjakan</small></div><span class="status status-{{ $k['status'] }}">{!! $k['status'] === 'terbit' ? 'Terbit' : 'Draft' !!}</span></li>
        @endforeach
      </ul>
    </section>
    @endif

    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('riwayat') !!} Aktivitas terbaru</h2></div>
      @if ($aktivitas->isNotEmpty())
        <ul class="aktivitas">
          @foreach ($aktivitas as $a)
            <li class="{!! warna($a['warna']) !!}"><span class="bulat">{!! icon('quiz') !!}</span><div class="isi"><b>{{ $a['judul'] }}</b><small>{{ tanggal($a['selesai_pada'] ?? $a['mulai_pada'], true) }}</small></div>
              {!! $a['status'] === 'selesai' ? '<span class="skor-pil">' . angka($a['skor']) . '</span>' : '<span class="status status-' . e($a['status']) . '">' . e(STATUS_ATTEMPT[$a['status']]) . '</span>' !!}</li>
          @endforeach
        </ul>
      @else<p class="muted mb-0">Belum ada aktivitas.</p>@endif
    </section>
  </div>
</div>
@endsection
