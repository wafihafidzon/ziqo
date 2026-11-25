@extends('layouts.app')

@section('konten')
@php $qs = http_build_query(array_filter(request()->query())); @endphp
<div class="kepala-halaman"><div><h1>Pengguna</h1><p>Kelola role dan status akun. Perubahan langsung berlaku pada hak akses pengguna.</p></div></div>

<div class="grid grid-4 mb-1">
  <div class="stat w-ungu"><span class="stat-ikon">{!! icon('pengguna') !!}</span><span class="stat-nilai">{!! (int) array_sum($jumlahRole) !!}</span><span class="stat-label">Total pengguna</span></div>
  <div class="stat w-biru"><span class="stat-ikon">{!! icon('user') !!}</span><span class="stat-nilai">{!! (int) ($jumlahRole['peserta'] ?? 0) !!}</span><span class="stat-label">Peserta</span></div>
  <div class="stat w-hijau"><span class="stat-ikon">{!! icon('pena') !!}</span><span class="stat-nilai">{!! (int) ($jumlahRole['pengajar'] ?? 0) !!}</span><span class="stat-label">Pengajar</span></div>
  <div class="stat w-pink"><span class="stat-ikon">{!! icon('perisai') !!}</span><span class="stat-nilai">{!! (int) ($jumlahRole['admin'] ?? 0) !!}</span><span class="stat-label">Administrator</span></div>
</div>

<form class="kartu mb-1" method="get" data-auto-kirim role="search" aria-label="Cari pengguna">
  <div class="baris-form baris-filter">
    <div class="field mb-0"><label class="label" for="u-q">Cari nama atau email</label>
      <div class="input-ikon">{!! icon('cari') !!}<input class="input" id="u-q" type="search" name="q" value="{{ $f['q'] }}" placeholder="mis. rina"></div></div>
    <div class="field mb-0"><label class="label" for="u-role">Role</label>
      <select class="input" id="u-role" name="role"><option value="">Semua role</option>@foreach (ROLE as $k => $l)<option value="{!! $k !!}" {!! $f['role'] === $k ? 'selected' : '' !!}>{{ $l }}</option>@endforeach</select></div>
    <div class="field mb-0"><label class="label" for="u-status">Status</label>
      <select class="input" id="u-status" name="status"><option value="">Semua status</option><option value="aktif" {!! $f['status'] === 'aktif' ? 'selected' : '' !!}>Aktif</option><option value="nonaktif" {!! $f['status'] === 'nonaktif' ? 'selected' : '' !!}>Nonaktif</option></select></div>
    <button class="btn" type="submit">{!! icon('cari') !!} Cari</button>
  </div>
</form>

<section class="kartu">
  <p class="kecil muted">{!! (int) $pg['total'] !!} pengguna ditemukan</p>
  @if ($rows->isNotEmpty())
    <div class="tabel-wrap"><table class="tabel">
      <thead><tr><th>Pengguna</th><th>Role</th><th>Status</th><th class="num">Kuis dibuat</th><th class="num">Dikerjakan</th><th>Bergabung</th><th><span class="sr-only">Aksi</span></th></tr></thead>
      <tbody>
      @php foreach ($rows as $r):
        $diri = (int) $r['id'] === (int) $admin['id'];
        $terlindungi = $r['role'] === 'admin'; @endphp
        <tr>
          <td><a class="orang" href="{{ route('admin.pengguna.show', $r['id']) }}" style="color:inherit"><img class="avatar" src="{{ avatar_url($r) }}" alt=""><span><b>{{ $r['nama'] }}{!! $diri ? ' (kamu)' : '' !!}</b><small>{{ $r['email'] }}</small></span></a></td>
          <td>
            @if ($terlindungi)
              <span class="chip w-pink">{!! icon('perisai') !!} Administrator</span>
            @else
              <form method="post" class="baris" style="gap:.3rem">
                @csrf<input type="hidden" name="id" value="{!! (int) $r['id'] !!}"><input type="hidden" name="aksi" value="role"><input type="hidden" name="qs" value="{{ $qs }}">
                <label class="sr-only" for="role-{!! (int) $r['id'] !!}">Role {{ $r['nama'] }}</label>
                <select class="input" id="role-{!! (int) $r['id'] !!}" name="role" style="min-height:38px;padding:.3rem 2.2rem .3rem .75rem;width:auto" onchange="this.form.requestSubmit()">
                  <option value="peserta" {!! $r['role'] === 'peserta' ? 'selected' : '' !!}>Peserta</option>
                  <option value="pengajar" {!! $r['role'] === 'pengajar' ? 'selected' : '' !!}>Pengajar</option>
                </select>
                <noscript><button class="btn btn-kecil" type="submit">Ubah</button></noscript>
              </form>
            @endif
          </td>
          <td><span class="status status-{{ $r['status'] }}">{!! $r['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' !!}</span></td>
          <td class="num">{!! (int) $r['jumlah_kuis'] !!}</td>
          <td class="num">{!! (int) $r['jumlah_percobaan'] !!}</td>
          <td>{{ tanggal($r['created_at']) }}</td>
          <td><div class="aksi">
            @if (!$terlindungi && !$diri)
              <form method="post" {!! $r['status'] === 'aktif' ? 'data-confirm="' . e($r['nama']) . ' tidak akan bisa masuk atau mengakses fitur ZIQO sampai diaktifkan kembali." data-confirm-judul="Nonaktifkan akun?" data-confirm-ok="Nonaktifkan" data-bahaya' : '' !!}>
                @csrf<input type="hidden" name="id" value="{!! (int) $r['id'] !!}"><input type="hidden" name="aksi" value="status"><input type="hidden" name="qs" value="{{ $qs }}">
                @if ($r['status'] === 'aktif')
                  <button class="btn btn-kecil btn-garis" name="status" value="nonaktif" type="submit" style="--fg:var(--merah)">{!! icon('power') !!} Nonaktifkan</button>
                @else
                  <button class="btn btn-kecil btn-hijau" name="status" value="aktif" type="submit">{!! icon('power') !!} Aktifkan</button>
                @endif
              </form>
            @endif
            <a class="btn btn-kecil btn-putih" href="{{ route('admin.pengguna.show', $r['id']) }}">Detail</a>
          </div></td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    @include('partials.paginasi', ['pg' => $pg])
  @else
    @include('partials.kosong', ['judul' => 'Pengguna tidak ditemukan', 'pesan' => 'Coba kata kunci atau filter lain.', 'aksi' => ['Reset filter', route('admin.pengguna')]])
  @endif
</section>
@endsection
