@extends('layouts.app')

@section('konten')
@php $gayaKode = 'font-family:ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.12em'; @endphp
<div class="kepala-halaman">
  <div>
    <h1>{{ $admin ? 'Pantau room' : 'Room saya' }}</h1>
    <p>{{ $admin ? 'Seluruh room dari semua pengajar.' : 'Semua room dari kuis buatanmu.' }} Ada <b>{!! (int) $aktif !!}</b> room yang sedang dibuka. Buat room baru dari menu <a href="{{ kuis_induk()[1] }}">{{ $admin ? 'Pantau Kuis' : 'Kuis Saya' }}</a>.</p>
  </div>
</div>

<form class="kartu mb-1" method="get" data-auto-kirim role="search" aria-label="Filter room">
  <div class="baris-form baris-filter">
    <div class="field mb-0"><label class="label" for="r-q">Cari kode atau judul kuis</label><div class="input-ikon">{!! icon('cari') !!}<input class="input" id="r-q" type="search" name="q" value="{{ $f['q'] }}"></div></div>
    <div class="field mb-0"><label class="label" for="r-st">Status</label><select class="input" id="r-st" name="status"><option value="">Semua</option>@foreach (\App\Models\Room::STATUS as $k => $v)<option value="{{ $k }}" {!! $f['status'] === $k ? 'selected' : '' !!}>{{ $v }}</option>@endforeach</select></div>
    <button class="btn" type="submit">{!! icon('cari') !!} Cari</button>
  </div>
</form>

<section class="kartu">
  <p class="kecil muted">{!! (int) $pg['total'] !!} room</p>
  @if ($rows->isNotEmpty())
    <div class="tabel-wrap"><table class="tabel">
      <thead><tr><th>Kode akses</th><th>Kuis</th><th>Status</th><th>Peserta</th><th class="num">Mengerjakan</th><th>Dibuat</th><th><span class="sr-only">Aksi</span></th></tr></thead>
      <tbody>
      @foreach ($rows as $r)
        @php $sisa = (int) $r->attempts_count - $r->attempts->count(); @endphp
        <tr>
          <td><b style="{{ $gayaKode }}">{{ $r->kode_akses }}</b></td>
          <td><span class="tebal">{{ $r->quiz?->judul }}</span>@if ($admin)<br><small class="muted">{{ $r->quiz?->owner?->nama }}</small>@endif</td>
          <td><span class="status status-{{ $r->status === 'buka' ? 'aktif' : 'nonaktif' }}">{{ \App\Models\Room::STATUS[$r->status] }}</span></td>
          <td>
            @if ($r->attempts_count)
              <b>{!! (int) $r->attempts_count !!}</b> <small class="muted">· {{ $r->attempts->map(fn ($a) => $a->user?->nama)->filter()->join(', ') }}{{ $sisa > 0 ? ' +' . $sisa . ' lainnya' : '' }}</small>
            @else
              <span class="muted">Belum ada</span>
            @endif
          </td>
          <td class="num">{!! (int) $r->sedang_count !!}</td>
          <td>{{ tanggal((string) $r->created_at, true) }}</td>
          <td><div class="aksi"><a class="btn btn-kecil btn-putih" href="{{ route('pengajar.room', [$r->quiz_id, 'room' => $r->id]) }}">{!! icon('pengguna') !!} Peserta</a></div></td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    @include('partials.paginasi', ['pg' => $pg])
  @elseif ($f['q'] !== '' || $f['status'] !== '')
    @include('partials.kosong', ['judul' => 'Tidak ada room', 'aksi' => ['Reset filter', route('pengajar.rooms')]])
  @else
    @include('partials.kosong', ['judul' => 'Belum ada room', 'pesan' => 'Buka kuis yang sudah terbit, lalu tekan tombol Room untuk membuat kode akses.'])
  @endif
</section>
@endsection
