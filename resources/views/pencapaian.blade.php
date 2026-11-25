@extends('layouts.app')

@section('konten')
<div class="kepala-halaman">
  <div>
    <h1>Pencapaian</h1>
    <p>Kamu memiliki <b>{!! (int) $dimiliki !!}</b> dari {!! count($koleksi) !!} badge. Badge diberikan otomatis oleh sistem saat syaratnya terpenuhi.</p>
  </div>
  <div style="min-width:220px">
    <div class="progress kuning" role="progressbar" aria-label="Koleksi badge" aria-valuemin="0" aria-valuemax="{!! count($koleksi) !!}" aria-valuenow="{!! (int) $dimiliki !!}"><span style="width:{!! count($koleksi) ? round($dimiliki / count($koleksi) * 100) : 0 !!}%"></span></div>
  </div>
</div>

<div class="grid-badge">
  @php foreach ($koleksi as $b): $punya = (bool) $b['diperoleh_pada']; @endphp
    <div class="lencana {!! warna($b['warna']) !!} {!! $punya ? '' : 'terkunci' !!}">
      <div class="lencana-ikon">{!! icon($b['ikon']) !!}</div>
      <b>{{ $b['nama'] }}</b>
      <small>{{ $b['deskripsi'] }}</small>
      @if ($punya)
        <span class="chip w-hijau mt-1">{!! icon('cek') !!} {{ tanggal($b['diperoleh_pada']) }}</span>
      @else
        <div class="progress" role="progressbar" aria-label="Kemajuan {{ $b['nama'] }}" aria-valuemin="0" aria-valuemax="{!! (int) $b['nilai_syarat'] !!}" aria-valuenow="{!! (int) $b['kemajuan'] !!}"><span style="width:{!! round($b['kemajuan'] / max(1, $b['nilai_syarat']) * 100) !!}%"></span></div>
        <small>{!! (int) $b['kemajuan'] !!}/{!! (int) $b['nilai_syarat'] !!}</small>
      @endif
    </div>
  @endforeach
</div>

<div class="alert alert-info mt-2">{!! icon('info') !!}<span>Badge bersifat permanen. Jika kamu mereset riwayat, badge yang sudah diperoleh tetap tersimpan dan kemajuan badge tetap menghitung kuis yang pernah kamu selesaikan.</span></div>
@endsection
