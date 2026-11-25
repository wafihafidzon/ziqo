@php
$ikonFlash = ['sukses' => 'cek', 'error' => 'peringatan', 'info' => 'info'];
$flash = session()->pull('toasts', []);
@endphp
<div class="toasts" aria-live="polite">
  @foreach ($flash as $f)
    <div class="toast toast-{{ $f['tipe'] }}" role="{!! $f['tipe'] === 'error' ? 'alert' : 'status' !!}">
      {!! icon($ikonFlash[$f['tipe']] ?? 'info') !!}
      <span>{{ $f['pesan'] }}</span>
      <button type="button" data-tutup-toast aria-label="Tutup notifikasi">{!! icon('silang') !!}</button>
    </div>
  @endforeach
</div>

<dialog class="dialog" id="dialog-konfirmasi" aria-labelledby="dk-judul">
  <div class="dialog-ikon" id="dk-ikon">{!! icon('peringatan') !!}</div>
  <h3 id="dk-judul">Yakin?</h3>
  <p id="dk-pesan"></p>
  <div class="dialog-aksi">
    <button type="button" class="btn btn-garis" data-batal>Batal</button>
    <button type="button" class="btn" id="dk-ok">Ya, lanjutkan</button>
  </div>
</dialog>
<noscript><div class="alert alert-kuning" style="margin:1rem">Aktifkan JavaScript agar seluruh fitur interaktif ZIQO dapat digunakan.</div></noscript>
