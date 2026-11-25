@if (($pg['maks'] ?? 1) > 1)
@php $link = fn (int $h) => '?' . http_build_query(array_merge(request()->query(), ['hal' => $h])); @endphp
<nav class="paginasi" aria-label="Halaman">
  @if ($pg['hal'] > 1)<a href="{{ $link($pg['hal'] - 1) }}" aria-label="Sebelumnya">{!! icon('panah-kiri') !!}</a>@endif
  @for ($i = max(1, $pg['hal'] - 2); $i <= min($pg['maks'], $pg['hal'] + 2); $i++)
    @if ($i === $pg['hal'])<span class="aktif" aria-current="page">{{ $i }}</span>
    @else<a href="{{ $link($i) }}">{{ $i }}</a>@endif
  @endfor
  @if ($pg['hal'] < $pg['maks'])<a href="{{ $link($pg['hal'] + 1) }}" aria-label="Berikutnya">{!! icon('panah-kanan') !!}</a>@endif
</nav>
@endif
