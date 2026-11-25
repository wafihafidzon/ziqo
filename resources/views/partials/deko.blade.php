@php
$varian = $varian ?? 'kartu';
@endphp
@if ($varian === 'kartu')
<svg class="deko" viewBox="0 0 120 120" aria-hidden="true">
  <circle cx="70" cy="70" r="46" fill="currentColor" opacity=".14"/>
  <path d="M70 34c-14 0-24 9-24 21h13c0-5 5-9 11-9s11 4 11 9c0 9-15 9-15 23v4h13v-3c0-10 16-11 16-25 0-12-11-20-25-20z" fill="currentColor" opacity=".35"/>
  <circle cx="69" cy="95" r="7" fill="currentColor" opacity=".35"/>
  <path d="m28 26 3.5 7.2 7.9 1.1-5.7 5.6 1.3 7.9-7-3.7-7 3.7 1.3-7.9-5.7-5.6 7.9-1.1z" fill="#f4cc65"/>
</svg>
@elseif ($varian === 'gelap')
<svg class="deko" viewBox="0 0 600 260" preserveAspectRatio="xMaxYMid slice" aria-hidden="true">
  <circle cx="520" cy="40" r="110" fill="#fff" opacity=".06"/>
  <circle cx="430" cy="230" r="70" fill="#fff" opacity=".05"/>
  <path transform="translate(300 150)" d="m80 30 5 10.3 11.3 1.6-8.2 8 2 11.3L80 56l-10.1 5.2 2-11.3-8.2-8 11.3-1.6z" fill="#f4cc65" opacity=".9"/>
  <path d="m360 200 3.5 7.2 7.9 1.1-5.7 5.6 1.3 7.9-7-3.7-7 3.7 1.3-7.9-5.7-5.6 7.9-1.1z" fill="#f4cc65" opacity=".7"/>
  <text x="250" y="90" font-family="Titan One, sans-serif" font-size="70" fill="#fff" opacity=".08">?</text>
  <text x="560" y="210" font-family="Titan One, sans-serif" font-size="90" fill="#fff" opacity=".08">?</text>
</svg>
@endif
