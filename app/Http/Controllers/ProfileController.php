<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StatsService;
use App\Services\UserService;
use App\Support\Avatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(private UserService $users)
    {
    }

    public function show(Request $request, StatsService $stats)
    {
        $tab = session('tab') ?? $request->input('tab');
        return view('profil', [
            'title' => 'Profil',
            'menu' => 'profil',
            'user' => $this->user(),
            'errors' => form_errors(),
            'tab' => in_array($tab, ['info', 'avatar', 'password'], true) ? $tab : 'info',
            'stat' => $stats->user($this->user()->id),
        ]);
    }

    public function update(Request $request)
    {
        $user = $this->user();
        return match ($request->input('aksi')) {
            'info' => $this->info($request, $user),
            'avatar' => $this->avatar($request, $user),
            'hapus-avatar' => $this->hapusAvatar($user),
            'password' => $this->password($request, $user),
            default => redirect()->route('profil'),
        };
    }

    private function info(Request $request, User $user)
    {
        $errors = $this->users->validateProfile($request->all());
        if ($errors) return $this->gagal($errors, ['tab' => 'info']);

        $this->users->updateProfile($user, [
            'nama' => $request->input('nama'),
            'telepon' => $request->input('telepon'),
            'jenjang' => $request->input('jenjang'),
            'bio' => $request->input('bio'),
        ]);
        flash('sukses', 'Profil berhasil diperbarui.');
        return redirect()->route('profil');
    }

    private function avatar(Request $request, User $user)
    {
        [$nama, $pesan] = Avatar::store($request->file('avatar'));
        if ($pesan) return $this->gagal(['avatar' => $pesan], ['tab' => 'avatar']);
        Avatar::delete($user->avatar);
        $this->users->updateProfile($user, ['avatar' => $nama]);
        flash('sukses', 'Foto profil berhasil diganti.');
        return redirect()->route('profil', ['tab' => 'avatar']);
    }

    private function hapusAvatar(User $user)
    {
        Avatar::delete($user->avatar);
        $this->users->updateProfile($user, ['avatar' => null]);
        flash('sukses', 'Foto profil dihapus.');
        return redirect()->route('profil', ['tab' => 'avatar']);
    }

    private function password(Request $request, User $user)
    {
        $errors = $this->users->validatePasswordChange($user, $request->all());
        if ($errors) return $this->gagal($errors, ['tab' => 'password']);

        $baru = $request->input('password_baru');
        $this->users->setPassword($user, $baru);
        // Hash baru membuat sesi lain milik akun ini ditolak oleh AuthenticateSession (bootstrap/app.php);
        // sesi ini sendiri menerima hash baru, dan ID sesi lama dihancurkan.
        Auth::logoutOtherDevices($baru);
        $request->session()->regenerate(true);
        flash('sukses', 'Password berhasil diganti.');
        return redirect()->route('profil', ['tab' => 'password']);
    }
}
