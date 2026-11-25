@extends('layouts.app')

@section('konten')
@php $g = fn($k, $d = '') => (string) ($v[$k] ?? $d); @endphp
<div class="remah"><a href="{{ kuis_induk()[1] }}">{{ kuis_induk()[0] }}</a> {!! icon('chevron') !!} <span>{!! $quiz ? e($quiz['judul']) : 'Kuis baru' !!}</span></div>
<div class="kepala-halaman"><div><h1>{!! $quiz ? 'Edit pengaturan kuis' : 'Buat kuis baru' !!}</h1><p>{!! $quiz ? 'Perubahan berlaku untuk pengerjaan berikutnya.' : 'Kuis baru disimpan sebagai draft sampai kamu menerbitkannya.' !!}</p></div></div>

<form method="post" class="grid grid-sidebar" data-validasi>
  @csrf
  <section class="kartu">
    <div class="kartu-judul"><h2>{!! icon('quiz') !!} Informasi kuis</h2></div>
    <div class="field {!! isset($errors['judul']) ? 'salah' : '' !!}">
      <label for="judul">Judul kuis</label>
      <input class="input" id="judul" name="judul" required minlength="5" maxlength="150" data-label="Judul" value="{{ $g('judul') }}" placeholder="mis. Persamaan Linear Satu Variabel"{!! aria_err($errors, 'judul') !!}>
      {!! err($errors, 'judul') !!}
    </div>
    <div class="field {!! isset($errors['deskripsi']) ? 'salah' : '' !!}">
      <label for="deskripsi">Deskripsi singkat</label>
      <textarea class="input" id="deskripsi" name="deskripsi" maxlength="1000" rows="3" placeholder="Apa yang dilatih kuis ini?">{{ $g('deskripsi') }}</textarea>
      {!! err($errors, 'deskripsi') !!}
    </div>
    <div class="baris-form">
      <div class="field {!! isset($errors['category_id']) ? 'salah' : '' !!}">
        <label for="category_id">Mata pelajaran</label>
        <select class="input" id="category_id" name="category_id" required data-label="Mata pelajaran"{!! aria_err($errors, 'category_id') !!}>
          <option value="">Pilih mata pelajaran</option>
          @foreach ($kategori as $c)<option value="{!! (int) $c['id'] !!}" {!! $g('category_id') == $c['id'] ? 'selected' : '' !!}>{{ $c['nama'] }}</option>@endforeach
        </select>
        {!! err($errors, 'category_id') !!}
      </div>
      <div class="field {!! isset($errors['subcategory_id']) ? 'salah' : '' !!}">
        <label for="subcategory_id">Topik <span class="muted">(opsional)</span></label>
        <select class="input" id="subcategory_id" name="subcategory_id" data-topik-untuk="category_id" data-kosong="Tanpa topik khusus"
                data-peta="{{ json_encode($subMap) }}" data-terpilih="{{ $g('subcategory_id') }}">
          <option value="">Tanpa topik khusus</option>
        </select>
        {!! err($errors, 'subcategory_id') !!}
      </div>
    </div>
    <div class="baris-form">
      <div class="field">
        <label for="kesulitan">Tingkat kesulitan</label>
        <select class="input" id="kesulitan" name="kesulitan" required>
          @foreach (KESULITAN as $k => $l)<option value="{!! $k !!}" {!! $g('kesulitan') === $k ? 'selected' : '' !!}>{{ $l }}</option>@endforeach
        </select>
      </div>
      <div class="field">
        <label for="jenjang">Jenjang pendidikan</label>
        <select class="input" id="jenjang" name="jenjang" required>
          @foreach (JENJANG as $k => $l)<option value="{!! $k !!}" {!! $g('jenjang') === $k ? 'selected' : '' !!}>{{ $l }}</option>@endforeach
        </select>
      </div>
    </div>
    <div class="field">
      <label for="petunjuk">Catatan / petunjuk tambahan <span class="muted">(opsional)</span></label>
      <textarea class="input" id="petunjuk" name="petunjuk" maxlength="1000" rows="3" placeholder="mis. Siapkan kertas coretan.">{{ $g('petunjuk') }}</textarea>
    </div>
  </section>

  <aside class="tumpuk">
    <section class="kartu">
      <div class="kartu-judul"><h2>{!! icon('jam') !!} Aturan pengerjaan</h2></div>
      <div class="field {!! isset($errors['durasi_menit']) ? 'salah' : '' !!}">
        <label for="durasi_menit">Batas waktu (menit)</label>
        <input class="input" id="durasi_menit" name="durasi_menit" type="number" min="0" max="180" required data-label="Batas waktu" value="{{ $g('durasi_menit', '0') }}"{!! aria_err($errors, 'durasi_menit') !!}>
        <div class="bantuan">Isi 0 untuk tanpa batas waktu. Maksimal 180 menit.</div>
        {!! err($errors, 'durasi_menit') !!}
      </div>
      <div class="field">
        <label for="akses">Akses kuis</label>
        <select class="input" id="akses" name="akses" required>
          @foreach (\App\Models\Quiz::AKSES as $k => $l)<option value="{!! $k !!}" {!! $g('akses') === $k ? 'selected' : '' !!}>{{ $l }}</option>@endforeach
        </select>
        <div class="bantuan">Publik: tampil di Jelajah dan bisa dikerjakan kapan saja. Hanya lewat room: tidak tampil di Jelajah; peserta masuk dengan kode room darimu (cocok untuk ulangan).</div>
      </div>
      <div class="field"><label class="cek"><input type="checkbox" name="tampilkan_feedback" value="1" {!! $g('tampilkan_feedback') ? 'checked' : '' !!}> Umpan balik langsung (tombol "Cek jawaban")</label>
        <div class="bantuan">Peserta melihat benar/salah dan pembahasan per soal. Jawaban yang sudah dicek terkunci.</div></div>
      <div class="field"><label class="cek"><input type="checkbox" name="navigasi_bebas" value="1" {!! $g('navigasi_bebas') ? 'checked' : '' !!}> Navigasi bebas antarsoal</label>
        <div class="bantuan">Jika dimatikan, soal dikerjakan berurutan dan tidak bisa kembali.</div></div>
    </section>
    <div class="aksi-form">
      <button class="btn btn-besar" type="submit">{!! icon('cek') !!} {!! $quiz ? 'Simpan' : 'Buat & tambah soal' !!}</button>
      <a class="btn btn-teks" href="{{ $quiz ? route('pengajar.soal', $quiz['id']) : kuis_induk()[1] }}">Batal</a>
    </div>
  </aside>
</form>
@endsection
