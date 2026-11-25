<div class="kosong">
  <img src="{{ asset('assets/maskot.webp') }}" alt="">
  <h3>{{ $judul ?? 'Belum ada data' }}</h3>
  @if (!empty($pesan))<p>{{ $pesan }}</p>@endif
  @if (!empty($aksi))<a class="btn" href="{{ $aksi[1] }}">{{ $aksi[0] }}</a>@endif
</div>
