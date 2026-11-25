<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if ($user && $user->status !== 'aktif') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            flash('error', 'Akun kamu sedang dinonaktifkan oleh administrator.');
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'pesan' => 'Sesi berakhir. Silakan masuk kembali.'], 401)
                : redirect()->route('login');
        }
        return $next($request);
    }
}
