<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    public function __construct(private UserService $users)
    {
    }

    public function showLogin(Request $request)
    {
        return view('auth.login', [
            'title' => 'Masuk',
            'error' => session('login_error'),
            'emailIngat' => (string) $request->cookie('ziqo_email', ''),
        ]);
    }

    public function login(Request $request)
    {
        $email = (string) $request->input('email');
        $password = (string) $request->input('password');
        $hasil = $email === '' || $password === '' ? 'Email dan password wajib diisi.' : $this->users->attemptLogin($email, $password);
        if (is_string($hasil)) {
            return back()->withInput($request->only('email', 'ingat'))->with('login_error', $hasil);
        }

        Auth::login($hasil);
        $request->session()->regenerate();
        $cookie = $request->boolean('ingat')
            ? Cookie::make('ziqo_email', $this->users->normalizeEmail($email), 60 * 24 * 30)
            : Cookie::forget('ziqo_email');
        flash('sukses', 'Selamat datang kembali, ' . $hasil->nama . '!');
        return redirect()->intended(route('dashboard'))->withCookie($cookie);
    }

    public function showRegister()
    {
        return view('auth.register', ['title' => 'Daftar', 'errors' => form_errors()]);
    }

    public function register(Request $request)
    {
        $errors = $this->users->validateRegistration($request->all());
        if ($errors) return $this->gagal($errors);

        $user = $this->users->register($request->input('nama'), $request->input('email'), $request->input('password'));
        Auth::login($user);
        $request->session()->regenerate();
        flash('sukses', 'Akun berhasil dibuat. Selamat datang di ZIQO!');
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        flash('info', 'Kamu sudah keluar. Sampai jumpa lagi!');
        return redirect()->route('login');
    }
}
