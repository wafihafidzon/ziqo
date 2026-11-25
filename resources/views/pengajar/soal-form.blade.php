@extends('layouts.app')

@section('konten')
@php
$opsi = array_values((array) ($v['opsi'] ?? []));
if (count($opsi) < 2) $opsi = array_pad($opsi, 2, '');
$kunci = $v['kunci'] ?? ($tipe === 'pg' ? '' : []);
$jawaban = array_values((array) ($v['jawaban'] ?? ['']));
if (!$jawaban) $jawaban = [''];
@endphp
<div class="remah"><a href="{{ kuis_induk()[1] }}">{{ kuis_induk()[0] }}</a> {!! icon('chevron') !!} <a href="{{ route('pengajar.soal', $quiz['id']) }}">{{ $quiz['judul'] }}</a> {!! icon('chevron') !!} <span>{!! $soal ? 'Edit soal' : 'Soal baru' !!}</span></div>
<div class="kepala-halaman"><div><h1>{!! $soal ? 'Edit soal' : 'Tambah soal' !!}</h1><p>Jenis: <b>{{ TIPE_SOAL[$tipe] }}</b></p></div></div>

@if ($errors)<div class="alert alert-error" role="alert">{!! icon('peringatan') !!}<span>Periksa kembali: {{ implode(' ', $errors) }}</span></div>@endif

<form method="post" class="grid grid-sidebar" data-validasi>
  @csrf
  <section class="kartu">
    <div class="field {!! isset($errors['teks']) ? 'salah' : '' !!}">
      <label for="teks">Pertanyaan</label>
      <textarea class="input" id="teks" name="teks" required minlength="5" maxlength="2000" rows="4" data-label="Pertanyaan" placeholder="Tulis pertanyaan di sini"{!! aria_err($errors, 'teks') !!}>{{ $v['teks'] ?? '' }}</textarea>
      {!! err($errors, 'teks') !!}
    </div>

    @if ($tipe === 'pg' || $tipe === 'pgk')
      <fieldset class="field" style="border:0;padding:0">
        <legend class="label">Opsi jawaban <span class="muted">(2–6 opsi; {!! $tipe === 'pg' ? 'pilih satu kunci' : 'centang semua yang benar' !!})</span></legend>
        <div class="opsi-editor" id="opsi-editor" data-tipe="{{ $tipe }}">
          @php foreach ($opsi as $i => $t):
            $cek = $tipe === 'pg' ? ((string) $kunci === (string) $i) : in_array((string) $i, array_map('strval', (array) $kunci), true); @endphp
            <div class="baris-opsi">
              <label class="tanda" title="Tandai sebagai jawaban benar">
                <input type="{!! $tipe === 'pg' ? 'radio' : 'checkbox' !!}" name="kunci{!! $tipe === 'pgk' ? '[]' : '' !!}" value="{!! $i !!}" {!! $cek ? 'checked' : '' !!} aria-label="Opsi {!! $i + 1 !!} benar">
              </label>
              <input class="input" name="opsi[]" maxlength="255" value="{{ $t }}" placeholder="Opsi {!! chr(65 + $i) !!}" aria-label="Teks opsi {!! $i + 1 !!}">
              <button class="btn btn-teks btn-ikon" type="button" data-hapus-opsi aria-label="Hapus opsi">{!! icon('silang') !!}</button>
            </div>
          @endforeach
        </div>
        <button class="btn btn-putih btn-kecil mt-1" type="button" id="tambah-opsi">{!! icon('plus') !!} Tambah opsi</button>
        {!! err($errors, 'opsi') !!}{!! err($errors, 'kunci') !!}
        @if ($tipe === 'pgk')<p class="bantuan kecil muted mt-1">Penilaian: peserta mendapat poin penuh hanya jika memilih <b>semua</b> jawaban benar tanpa memilih jawaban salah.</p>@endif
      </fieldset>

    @elseif ($tipe === 'bs')
      <fieldset class="field" style="border:0;padding:0">
        <legend class="label">Kunci jawaban</legend>
        <div class="chip-pilih">
          <label><input type="radio" name="kunci_bs" value="benar" {!! ($v['kunci_bs'] ?? '') === 'benar' ? 'checked' : '' !!}><span>Benar</span></label>
          <label><input type="radio" name="kunci_bs" value="salah" {!! ($v['kunci_bs'] ?? '') === 'salah' ? 'checked' : '' !!}><span>Salah</span></label>
        </div>
        {!! err($errors, 'kunci_bs') !!}
      </fieldset>

    @elseif ($tipe === 'isian')
      <fieldset class="field" style="border:0;padding:0">
        <legend class="label">Jawaban yang diterima <span class="muted">(boleh lebih dari satu variasi, mis. "6" dan "enam")</span></legend>
        <div class="opsi-editor" id="jawaban-editor">
          @foreach ($jawaban as $t)
            <div class="baris-opsi" style="grid-template-columns:1fr auto">
              <input class="input" name="jawaban[]" maxlength="255" value="{{ $t }}" placeholder="Jawaban benar" aria-label="Jawaban yang diterima">
              <button class="btn btn-teks btn-ikon" type="button" data-hapus-opsi aria-label="Hapus jawaban">{!! icon('silang') !!}</button>
            </div>
          @endforeach
        </div>
        <button class="btn btn-putih btn-kecil mt-1" type="button" id="tambah-jawaban">{!! icon('plus') !!} Tambah variasi</button>
        {!! err($errors, 'jawaban') !!}
      </fieldset>
      <fieldset class="field" style="border:0;padding:0">
        <legend class="label">Mode pencocokan</legend>
        <div class="chip-pilih">
          <label><input type="radio" name="pencocokan" value="longgar" {!! ($v['pencocokan'] ?? 'longgar') !== 'ketat' ? 'checked' : '' !!}><span>Longgar</span></label>
          <label><input type="radio" name="pencocokan" value="ketat" {!! ($v['pencocokan'] ?? '') === 'ketat' ? 'checked' : '' !!}><span>Ketat</span></label>
        </div>
        <p class="bantuan kecil muted mt-1">Longgar: huruf besar/kecil, spasi berlebih, dan titik di akhir diabaikan. Ketat: harus sama persis (kecuali spasi di awal/akhir).</p>
      </fieldset>

    @else
      <div class="alert alert-info">{!! icon('info') !!}<span>Soal esai tidak dinilai otomatis. Setelah peserta mengumpulkan, kamu menilainya di menu <b>Penilaian Esai</b> dengan memberi poin 0 sampai poin maksimal.</span></div>
    @endif

    <div class="field {!! isset($errors['pembahasan']) ? 'salah' : '' !!}">
      <label for="pembahasan">{!! $tipe === 'esai' ? 'Rubrik / contoh jawaban' : 'Pembahasan' !!} <span class="muted">(opsional, tampil setelah dijawab)</span></label>
      <textarea class="input" id="pembahasan" name="pembahasan" maxlength="2000" rows="3">{{ $v['pembahasan'] ?? '' }}</textarea>
    </div>
  </section>

  <aside class="tumpuk">
    <section class="kartu">
      <div class="field {!! isset($errors['poin']) ? 'salah' : '' !!}">
        <label for="poin">Poin soal</label>
        <input class="input" id="poin" name="poin" type="number" min="1" max="100" required data-label="Poin" value="{{ $v['poin'] ?? 10 }}"{!! aria_err($errors, 'poin') !!}>
        {!! err($errors, 'poin') !!}
      </div>
      <div class="tumpuk" style="gap:.6rem">
        <button class="btn btn-blok" type="submit">{!! icon('cek') !!} Simpan soal</button>
        @if (!$soal)<button class="btn btn-putih btn-blok" type="submit" name="lagi" value="1">{!! icon('plus') !!} Simpan &amp; tambah lagi</button>@endif
        <a class="btn btn-teks btn-blok" href="{{ route('pengajar.soal', $quiz['id']) }}">Batal</a>
      </div>
    </section>
  </aside>
</form>
@endsection
