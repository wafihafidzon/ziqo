@extends('layouts.app')

@section('konten')
<div class="kepala-halaman"><div><h1>Penilaian esai</h1><p>Pengerjaan berisi soal esai yang belum dinilai, diurutkan dari yang paling lama menunggu.</p></div></div>
<section class="kartu">
  @if (count($rows))
    <ul class="aktivitas">
      @foreach ($rows as $r)
        <li class="w-kuning">
          <span class="bulat">{!! icon('pena') !!}</span>
          <div class="isi"><b>{{ $r['nama'] }} · {{ $r['judul'] }}</b><small>Dikumpulkan {{ tanggal($r['selesai_pada'], true) }}</small></div>
          <a class="btn btn-kecil btn-kuning" href="{{ route('pengajar.nilai', $r['id']) }}">Nilai sekarang</a>
        </li>
      @endforeach
    </ul>
  @else
    @include('partials.kosong', ['judul' => 'Semua esai sudah dinilai', 'pesan' => 'Tidak ada pengerjaan yang menunggu penilaian. Kerja bagus!'])
  @endif
</section>
@endsection
