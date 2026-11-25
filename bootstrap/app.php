<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [EnsureUserIsActive::class]);
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->redirectGuestsTo(function () {
            flash('info', 'Silakan masuk terlebih dahulu.');
            return route('login');
        });
        $middleware->redirectUsersTo(fn () => route('dashboard'));
        $middleware->encryptCookies(except: ['ziqo_email']);
        // Sesi yang hash password-nya sudah tidak cocok (password diganti di perangkat lain) langsung dikeluarkan
        $middleware->authenticateSessions();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $judul = [403 => 'Akses ditolak', 404 => 'Halaman tidak ditemukan', 419 => 'Sesi formulir kedaluwarsa', 429 => 'Terlalu banyak percobaan'];
        $pesan = [
            403 => 'Kamu tidak memiliki izin untuk membuka halaman ini.',
            404 => 'Halaman atau data yang kamu cari tidak ada.',
            419 => 'Formulir tidak dapat diproses karena token keamanan tidak cocok. Muat ulang halaman lalu coba lagi.',
            429 => 'Terlalu banyak percobaan. Coba lagi dalam beberapa saat.',
        ];
        $tampil = function (Request $request, int $kode, string $teks) use ($judul) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'pesan' => $teks], $kode)
                : response()->view('error', ['kode' => $kode, 'judul' => $judul[$kode], 'pesan' => $teks, 'title' => $judul[$kode]], $kode);
        };

        $exceptions->render(fn (TokenMismatchException $e, Request $request) => $tampil($request, 419, $request->expectsJson()
            ? 'Sesi formulir kedaluwarsa. Muat ulang halaman.' : $pesan[419]));

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($judul, $pesan, $tampil) {
            $kode = $e->getStatusCode();
            if (!isset($judul[$kode])) return null;
            // Pesan internal findOrFail ("No query results for model ...") dan throttle ("Too Many Attempts.") tidak boleh tampil ke pengguna
            $teks = $e->getPrevious() instanceof ModelNotFoundException || $kode === 429 ? '' : $e->getMessage();
            // Header seperti Retry-After dari throttle tetap dikirim
            return $tampil($request, $kode, $teks ?: $pesan[$kode])->withHeaders($e->getHeaders());
        });
    })->create();
