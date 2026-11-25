<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Avatar
{
    public const MAKS_BYTE = 2 * 1024 * 1024;
    private const TIPE = ['image/jpeg' => 'jpeg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public static function url(string $nama): string
    {
        return Storage::disk('public')->url('avatars/' . $nama);
    }

    public static function exists(?string $nama): bool
    {
        return $nama && self::valid($nama) && Storage::disk('public')->exists('avatars/' . $nama);
    }

    private static function valid(string $nama): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}\.(webp|png)$/', $nama);
    }

    /** @return array{0: ?string, 1: ?string} [nama file, pesan error] */
    public static function store(?UploadedFile $file): array
    {
        if (!$file) return [null, 'Pilih file gambar terlebih dahulu.'];
        if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) return [null, 'Ukuran file maksimal 2 MB.'];
        if (!$file->isValid()) return [null, 'Upload gagal. Coba lagi.'];
        if ($file->getSize() > self::MAKS_BYTE) return [null, 'Ukuran file maksimal 2 MB.'];

        $mime = $file->getMimeType();
        if (!isset(self::TIPE[$mime])) return [null, 'Format harus JPG, PNG, atau WEBP.'];
        $path = $file->getRealPath();
        $info = @getimagesize($path);
        if (!$info || $info[0] < 64 || $info[1] < 64) return [null, 'Gambar minimal berukuran 64×64 piksel.'];
        if ($info[0] > 5000 || $info[1] > 5000) return [null, 'Dimensi gambar maksimal 5000×5000 piksel.'];

        $src = match (self::TIPE[$mime]) {
            'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => @imagecreatefromwebp($path),
        };
        if (!$src) return [null, 'File gambar rusak atau tidak dapat dibaca.'];

        $w = imagesx($src);
        $h = imagesy($src);
        $sisi = min($w, $h);
        $dst = imagecreatetruecolor(256, 256);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $sisi, 2), intdiv($h - $sisi, 2), 256, 256, $sisi, $sisi);

        $webp = function_exists('imagewebp');
        $nama = bin2hex(random_bytes(16)) . ($webp ? '.webp' : '.png');
        ob_start();
        $ok = $webp ? imagewebp($dst, null, 85) : imagepng($dst, null, 6);
        $isi = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);
        if (!$ok || !Storage::disk('public')->put('avatars/' . $nama, $isi)) return [null, 'Gagal menyimpan gambar.'];
        return [$nama, null];
    }

    public static function delete(?string $nama): void
    {
        if ($nama && self::valid($nama)) Storage::disk('public')->delete('avatars/' . basename($nama));
    }
}
