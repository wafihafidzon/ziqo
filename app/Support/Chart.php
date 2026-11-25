<?php

namespace App\Support;

class Chart
{
    public static function line(array $rows, int $w = 520, int $h = 230): string
    {
        $n = count($rows);
        if ($n === 0) return '';
        $pl = 34; $pr = 14; $pt = 14; $pb = 30;
        $iw = $w - $pl - $pr; $ih = $h - $pt - $pb;
        $x = fn($i) => $pl + ($n === 1 ? $iw / 2 : $i * $iw / ($n - 1));
        $y = fn($v) => $pt + $ih - max(0, min(100, (float) $v)) / 100 * $ih;

        $svg = '<svg class="grafik" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Grafik perkembangan nilai">';
        $svg .= '<defs><linearGradient id="gradUngu" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#7566c9" stop-opacity=".22"/><stop offset="1" stop-color="#7566c9" stop-opacity="0"/></linearGradient></defs>';
        foreach ([0, 25, 50, 75, 100] as $g) {
            $gy = round($y($g), 1);
            $svg .= '<line class="' . ($g === 0 ? 'sumbu' : 'grid-garis') . '" x1="' . $pl . '" x2="' . ($w - $pr) . '" y1="' . $gy . '" y2="' . $gy . '"/>';
            $svg .= '<text class="label" x="' . ($pl - 8) . '" y="' . ($gy + 4) . '" text-anchor="end">' . $g . '</text>';
        }
        $pts = [];
        foreach ($rows as $i => $r) $pts[] = round($x($i), 1) . ',' . round($y($r['nilai']), 1);
        if ($n > 1) {
            $svg .= '<polygon class="area" points="' . round($x(0), 1) . ',' . ($pt + $ih) . ' ' . implode(' ', $pts) . ' ' . round($x($n - 1), 1) . ',' . ($pt + $ih) . '"/>';
            $svg .= '<polyline class="garis" points="' . implode(' ', $pts) . '"/>';
        }
        $langkah = max(1, (int) ceil($n / 8));
        foreach ($rows as $i => $r) {
            $cx = round($x($i), 1); $cy = round($y($r['nilai']), 1);
            $svg .= '<circle class="titik" cx="' . $cx . '" cy="' . $cy . '" r="4.5"><title>' . e($r['tip'] ?? $r['nilai']) . '</title></circle>';
            if ($i % $langkah === 0 || $i === $n - 1) {
                $svg .= '<text class="label" x="' . $cx . '" y="' . ($h - 8) . '" text-anchor="middle">' . e($r['label']) . '</text>';
            }
        }
        $last = $rows[$n - 1];
        $svg .= '<text class="nilai" x="' . round($x($n - 1), 1) . '" y="' . (round($y($last['nilai']), 1) - 10) . '" text-anchor="' . ($n > 1 ? 'end' : 'middle') . '">' . e(angka($last['nilai'])) . '</text>';
        return $svg . '</svg>';
    }

    public static function bars(array $rows, int $w = 520, int $h = 220): string
    {
        $n = count($rows);
        if ($n === 0) return '';
        $pl = 30; $pr = 8; $pt = 18; $pb = 28;
        $iw = $w - $pl - $pr; $ih = $h - $pt - $pb;
        $maks = max(1, max(array_column($rows, 'nilai')));
        $skala = $maks <= 4 ? 4 : (int) (ceil($maks / 4) * 4);
        $slot = $iw / $n; $bw = max(4, min(28, $slot - 6));
        $svg = '<svg class="grafik" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Grafik batang aktivitas">';
        foreach ([0, .5, 1] as $f) {
            $gy = round($pt + $ih - $f * $ih, 1);
            $svg .= '<line class="' . ($f == 0 ? 'sumbu' : 'grid-garis') . '" x1="' . $pl . '" x2="' . ($w - $pr) . '" y1="' . $gy . '" y2="' . $gy . '"/>';
            $svg .= '<text class="label" x="' . ($pl - 6) . '" y="' . ($gy + 4) . '" text-anchor="end">' . (int) round($f * $skala) . '</text>';
        }
        $langkah = max(1, (int) ceil($n / 7));
        foreach ($rows as $i => $r) {
            $bh = $r['nilai'] / $skala * $ih;
            $bx = round($pl + $i * $slot + ($slot - $bw) / 2, 1);
            $by = round($pt + $ih - $bh, 1);
            if ($r['nilai'] > 0) {
                $svg .= '<path class="batang" d="' . self::barPath($bx, $by, $bw, $bh) . '"><title>' . e($r['tip'] ?? $r['nilai']) . '</title></path>';
            } else {
                $svg .= '<rect x="' . $bx . '" y="' . ($pt) . '" width="' . $bw . '" height="' . $ih . '" fill="transparent"><title>' . e($r['tip'] ?? '0') . '</title></rect>';
            }
            if ($i % $langkah === 0 || $i === $n - 1) {
                $svg .= '<text class="label" x="' . round($bx + $bw / 2, 1) . '" y="' . ($h - 8) . '" text-anchor="middle">' . e($r['label']) . '</text>';
            }
        }
        return $svg . '</svg>';
    }

    private static function barPath(float $x, float $y, float $w, float $h): string
    {
        $r = min(4, $w / 2, $h);
        $b = $y + $h;
        return sprintf('M%.1f %.1fV%.1fQ%.1f %.1f %.1f %.1fH%.1fQ%.1f %.1f %.1f %.1fV%.1fZ',
            $x, $b, $y + $r, $x, $y, $x + $r, $y, $x + $w - $r, $x + $w, $y, $x + $w, $y + $r, $b);
    }
}
