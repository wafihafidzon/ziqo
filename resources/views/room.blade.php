@extends('layouts.app')

@section('konten')
<div class="kepala-halaman">
  <div><h1>Masuk room</h1><p>Masukkan kode akses dari pengajarmu untuk mulai mengerjakan kuis.</p></div>
</div>

<section class="kartu" style="max-width:32rem">
  <form method="post" action="{{ route('room') }}" data-validasi>
    @csrf
    <div class="field {!! isset($errors['kode']) ? 'salah' : '' !!}">
      <label for="kode">Kode akses</label>
      <input class="input" id="kode" name="kode" required maxlength="12" autocomplete="off" autocapitalize="characters" spellcheck="false" data-label="Kode akses"
        style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.2em;text-transform:uppercase;font-size:1.25rem"
        value="{{ $kode }}" placeholder="mis. K7QX2M"{!! aria_err($errors, 'kode') !!}>
      {!! err($errors, 'kode') !!}
    </div>
    <button class="btn" type="submit">{!! icon('panah-kanan') !!} Masuk dan mulai</button>
  </form>
</section>
@endsection
