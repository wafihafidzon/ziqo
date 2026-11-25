@extends('layouts.app')

@section('konten')
@php
$menunggu = $a['status'] === 'menunggu';
$totalSoal = count($detail);
$benar = (int) $a['jumlah_benar'];
$salah = (int) $a['jumlah_salah'];
$persen = $totalSoal ? round($benar / $totalSoal * 100) : 0;
$skor = $a['skor'] !== null ? (float) $a['skor'] : null;
$judul = $menunggu ? 'Jawaban terkirim!' : ($skor >= 100 ? 'Sempurna!' : ($skor >= 80 ? 'Luar biasa!' : ($skor >= 60 ? 'Kerja bagus!' : 'Terus berlatih!')));
@endphp
<div class="remah"><a href="{{ route('riwayat') }}">Riwayat</a> {!! icon('chevron') !!} <span>Hasil kuis</span></div>

<section class="hasil-hero">
  <span class="chip {!! warna($quiz['warna']) !!}">{{ $quiz['kategori'] }}</span>
  <h1 class="mt-1">{{ $judul }}</h1>
  <p class="muted mb-0">{{ $quiz['judul'] }} · {{ tanggal($a['selesai_pada'], true) }}</p>

  <div class="cincin {!! $menunggu ? 'menunggu' : '' !!}" style="--p: {!! $skor !== null ? (float) $skor : 0 !!}" role="img" aria-label="{!! $menunggu ? 'Nilai menunggu penilaian esai' : 'Nilai ' . e(angka($skor)) . ' dari 100' !!}">
    <div>@if ($menunggu)<b style="font-size:1.6rem">Menunggu</b><small>penilaian esai</small>@else<b>{!! angka($skor) !!}</b><small>dari 100</small>@endif</div>
  </div>

  <div class="ringkas-hasil">
    <div class="w-hijau"><b>{!! $benar !!}</b><span>Jawaban benar</span></div>
    <div class="w-merah"><b>{!! $salah !!}</b><span>Jawaban salah</span></div>
    <div class="w-biru"><b>{!! $persen !!}%</b><span>Persentase benar</span></div>
    <div class="w-ungu"><b>{{ durasi($a['durasi_detik'] !== null ? (int) $a['durasi_detik'] : null) }}</b><span>Waktu pengerjaan</span></div>
  </div>
  <p class="kecil muted mt-1 mb-0">Poin: {!! angka($a['poin_didapat'], 1) !!} / {!! angka($a['poin_maks'], 1) !!} XP{!! $menunggu ? ' (belum termasuk esai)' : '' !!}</p>

  @if ($menunggu)
    <div class="alert alert-kuning mt-1" style="max-width:560px;margin-inline:auto">{!! icon('info') !!}<span>Soal esai sedang menunggu penilaian pengajar. Nilai akhir, XP, dan badge akan diperbarui setelah dinilai.</span></div>
  @endif

  @if (count($badgeBaru))
    <div class="badge-baru">
      @foreach ($badgeBaru as $b)
        <div class="baris"><span class="lencana-mini {!! warna($b['warna']) !!}">{!! icon($b['ikon']) !!}</span><div><b>Badge baru: {{ $b['nama'] }}</b><br><small class="muted">{{ $b['deskripsi'] }}</small></div></div>
      @endforeach
    </div>
  @endif

  <div class="baris mt-2" style="justify-content:center">
    <form method="post" action="{{ route('kuis.show', $quiz['id']) }}">@csrf<button class="btn btn-putih" type="submit">{!! icon('reset') !!} Kerjakan lagi</button></form>
    <a class="btn" href="{{ route('jelajah', kueri(['sort' => 'rekomendasi'])) }}">{!! icon('jelajah') !!} Kuis lainnya</a>
    <a class="btn btn-kuning" href="{{ route('riwayat') }}">{!! icon('riwayat') !!} Lihat riwayat</a>
  </div>
</section>

<h2 class="mt-2" style="font-size:1.2rem">Pembahasan</h2>
<div class="bahas">
  @php foreach ($detail as $i => $s):
    $j = $s['jawaban'];
    $state = $j === null ? 'salah' : ($j['poin'] === null ? 'menunggu' : ((int) $j['benar'] === 1 ? 'benar' : 'salah'));
    $dipilih = $j['opsi'] ?? []; @endphp
    <article class="bahas-item {!! $state !!}">
      <div class="baris-antara mb-1">
        <div class="baris"><span class="chip">Soal {!! $i + 1 !!}</span><span class="chip chip-garis">{{ TIPE_SOAL[$s['tipe']] }}</span></div>
        <span class="tebal kecil" style="color:var(--{!! $state === 'benar' ? 'hijau' : ($state === 'menunggu' ? 'oranye' : 'merah') !!})">
          {!! $state === 'menunggu' ? 'Menunggu penilaian' : ($state === 'benar' ? 'Benar' : 'Salah') !!} · {!! $j && $j['poin'] !== null ? angka($j['poin'], 1) : '–' !!}/{!! (int) $s['poin'] !!} poin
        </span>
      </div>
      <p class="soal-teks">{{ $s['teks'] }}</p>
      @if (in_array($s['tipe'], ['pg', 'pgk', 'bs'], true))
        <ul class="bahas-opsi">
          @php foreach ($s['opsi'] as $o):
            $isKunci = (int) $o['benar'] === 1; $isPilih = in_array((int) $o['id'], $dipilih, true); @endphp
            <li class="{!! $isKunci ? 'kunci' : ($isPilih ? 'dipilih-salah' : '') !!}">
              {!! $isKunci ? icon('cek') : ($isPilih ? icon('silang') : '<span style="width:1.1rem;display:inline-block"></span>') !!}
              {{ $o['teks'] }}
              @if ($isPilih)<span class="chip" style="margin-left:auto">Jawabanmu</span>@endif
            </li>
          @endforeach
        </ul>
        @if (!$dipilih)<p class="kecil muted">Tidak dijawab.</p>@endif
      @else
        <div class="jawaban-teks"><b class="kecil muted">Jawabanmu:</b><br>{!! $j && $j['teks'] !== null && $j['teks'] !== '' ? e($j['teks']) : '<i class="muted">Tidak dijawab</i>' !!}</div>
        @if ($s['tipe'] === 'isian')<p class="kecil"><b>Jawaban yang diterima:</b> {{ implode(' / ', array_column($s['opsi'], 'teks')) }}</p>@endif
        @if ($s['tipe'] === 'esai' && $j && $j['catatan'])<div class="alert alert-info">{!! icon('pena') !!}<span><b>Catatan pengajar:</b> {{ $j['catatan'] }}</span></div>@endif
      @endif
      @if ($s['pembahasan'] && ($state !== 'menunggu'))<div class="pembahasan"><b>Pembahasan</b>{{ $s['pembahasan'] }}</div>@endif
    </article>
  @endforeach
</div>
@endsection
