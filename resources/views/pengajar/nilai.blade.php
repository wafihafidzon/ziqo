@extends('layouts.app')

@section('konten')
@php $adaEsai = (bool) array_filter($detail, fn($s) => $s['tipe'] === 'esai'); @endphp
<div class="remah"><a href="{{ route('pengajar.penilaian') }}">Penilaian</a> {!! icon('chevron') !!} <a href="{{ route('pengajar.hasil', $quiz['id']) }}">{{ $quiz['judul'] }}</a> {!! icon('chevron') !!} <span>{{ $peserta['nama'] }}</span></div>
<div class="kepala-halaman">
  <div class="orang">
    <img class="avatar" style="width:56px;height:56px" src="{{ avatar_url($peserta) }}" alt="">
    <div><h1 style="font-size:1.6rem">{{ $peserta['nama'] }}</h1><p class="mb-0">{{ $quiz['judul'] }} · dikumpulkan {{ tanggal($a['selesai_pada'], true) }} · {{ durasi((int) $a['durasi_detik']) }}</p></div>
  </div>
  <div class="stat" style="min-width:180px;text-align:center">
    <span class="stat-nilai">{!! $a['skor'] !== null ? angka($a['skor']) : '–' !!}</span>
    <span class="stat-label">{!! $a['status'] === 'menunggu' ? 'Menunggu penilaian esai' : 'Nilai akhir' !!}</span>
  </div>
</div>

<form method="post">
  @csrf
  <div class="bahas">
    @php foreach ($detail as $i => $s):
      $j = $s['jawaban'];
      $state = $j === null ? 'salah' : ($j['poin'] === null ? 'menunggu' : ((int) $j['benar'] === 1 ? 'benar' : 'salah'));
      $dipilih = $j['opsi'] ?? []; @endphp
      <article class="bahas-item {!! $state !!}">
        <div class="baris-antara mb-1">
          <div class="baris"><span class="chip">Soal {!! $i + 1 !!}</span><span class="chip chip-garis">{{ TIPE_SOAL[$s['tipe']] }}</span></div>
          <span class="tebal kecil">{!! $j && $j['poin'] !== null ? angka($j['poin'], 1) : '–' !!}/{!! (int) $s['poin'] !!} poin</span>
        </div>
        <p class="soal-teks">{{ $s['teks'] }}</p>
        @if (in_array($s['tipe'], ['pg', 'pgk', 'bs'], true))
          <ul class="bahas-opsi">
            @php foreach ($s['opsi'] as $o): $k = (int) $o['benar'] === 1; $p = in_array((int) $o['id'], $dipilih, true); @endphp
              <li class="{!! $k ? 'kunci' : ($p ? 'dipilih-salah' : '') !!}">{!! $k ? icon('cek') : ($p ? icon('silang') : '<span style="width:1.1rem;display:inline-block"></span>') !!}{{ $o['teks'] }}@if ($p)<span class="chip" style="margin-left:auto">Dipilih</span>@endif</li>
            @endforeach
          </ul>
        @else
          <div class="jawaban-teks">{!! $j && ($j['teks'] ?? '') !== '' ? e($j['teks']) : '<i class="muted">Tidak dijawab</i>' !!}</div>
          @if ($s['tipe'] === 'isian')<p class="kecil mb-0"><b>Diterima:</b> {{ implode(' / ', array_column($s['opsi'], 'teks')) }}</p>@endif
          @if ($s['tipe'] === 'esai' && $j)
            @if ($s['pembahasan'])<div class="pembahasan mb-1"><b>Rubrik / contoh jawaban</b>{{ $s['pembahasan'] }}</div>@endif
            <div class="baris-form" style="grid-template-columns:160px 1fr">
              <div class="field {!! isset($errors[$j['id']]) ? 'salah' : '' !!}">
                <label for="poin-{!! (int) $j['id'] !!}">Poin (0–{!! (int) $s['poin'] !!})</label>
                <input class="input" type="number" step="0.5" min="0" max="{!! (int) $s['poin'] !!}" id="poin-{!! (int) $j['id'] !!}" name="poin[{!! (int) $j['id'] !!}]" value="{{ old('poin.' . $j['id'], $j['poin'] ?? '') }}">
                @if (isset($errors[$j['id']]))<div class="pesan-salah">{!! icon('peringatan') !!}{{ $errors[$j['id']] }}</div>@endif
              </div>
              <div class="field">
                <label for="catatan-{!! (int) $j['id'] !!}">Catatan untuk peserta <span class="muted">(opsional)</span></label>
                <input class="input" id="catatan-{!! (int) $j['id'] !!}" name="catatan[{!! (int) $j['id'] !!}]" maxlength="500" value="{{ old('catatan.' . $j['id'], $j['catatan'] ?? '') }}">
              </div>
            </div>
          @endif
        @endif
      </article>
    @endforeach
  </div>
  @if ($adaEsai)
    <div class="aksi-form mt-2" style="position:sticky;bottom:1rem">
      <button class="btn btn-besar" type="submit">{!! icon('cek') !!} Simpan penilaian</button>
      <a class="btn btn-putih" href="{{ route('pengajar.penilaian') }}">Kembali</a>
    </div>
  @endif
</form>
@endsection
