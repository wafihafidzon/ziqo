<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    private const PROFIL = ['nama', 'telepon', 'jenjang', 'bio', 'avatar'];

    public function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $this->normalizeEmail($email))->first();
    }

    public function validateRegistration(array $in): array
    {
        $errors = validasi($in, [
            'nama' => 'required|min:3|max:100',
            'email' => 'required|email|max:190',
            'password' => 'required|min:8|max:72',
            'konfirmasi' => 'required|same:password',
        ], ['nama' => 'Nama', 'email' => 'Email', 'password' => 'Password', 'konfirmasi' => 'Konfirmasi password']);
        if (!$errors && $this->findByEmail((string) $in['email'])) {
            $errors['email'] = 'Email sudah terdaftar. Silakan masuk.';
        }
        return $errors;
    }

    /** Akun baru selalu Peserta yang aktif. */
    public function register(string $nama, string $email, string $password): User
    {
        return User::create([
            'nama' => $nama,
            'email' => $this->normalizeEmail($email),
            'password_hash' => Hash::make($password),
            'role' => 'peserta',
            'status' => 'aktif',
        ]);
    }

    /** User bila berhasil, atau pesan galat. Pesan sengaja sama untuk email maupun password yang salah. */
    public function attemptLogin(string $email, string $password): User|string
    {
        $user = $this->findByEmail($email);
        if (!$user || !Hash::check($password, $user->password_hash)) return 'Email atau password belum sesuai.';
        if ($user->status !== 'aktif') return 'Akun kamu sedang dinonaktifkan. Hubungi administrator.';
        if (Hash::needsRehash($user->password_hash)) $this->setPassword($user, $password);
        return $user;
    }

    public function validateProfile(array $in): array
    {
        $errors = validasi($in, [
            'nama' => 'required|min:3|max:100',
            'telepon' => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'],
            'jenjang' => 'nullable|in:' . implode(',', array_keys(JENJANG)),
            'bio' => 'nullable|max:300',
        ], ['nama' => 'Nama', 'telepon' => 'No. telepon', 'jenjang' => 'Jenjang', 'bio' => 'Bio']);
        if (isset($errors['telepon'])) $errors['telepon'] = 'No. telepon hanya boleh angka (8–15 digit).';
        return $errors;
    }

    /** Hanya kolom profil yang boleh diubah pemilik akun (bukan email, role, status). */
    public function updateProfile(User $user, array $data): void
    {
        $user->update(array_intersect_key($data, array_flip(self::PROFIL)));
    }

    public function validatePasswordChange(User $user, array $in): array
    {
        $errors = validasi($in, [
            'password_lama' => 'required',
            'password_baru' => 'required|min:8|max:72',
            'konfirmasi' => 'required|same:password_baru',
        ], ['password_lama' => 'Password saat ini', 'password_baru' => 'Password baru', 'konfirmasi' => 'Konfirmasi password']);
        if (!$errors && !Hash::check((string) $in['password_lama'], $user->password_hash)) {
            $errors['password_lama'] = 'Password saat ini salah.';
        }
        return $errors;
    }

    public function setPassword(User $user, string $password): void
    {
        $user->update(['password_hash' => Hash::make($password)]);
    }

    /** Filter admin dari input mentah; nilai tidak dikenal diabaikan. */
    public function filters(array $in): array
    {
        $role = (string) ($in['role'] ?? '');
        $status = (string) ($in['status'] ?? '');
        return [
            'q' => mb_substr((string) ($in['q'] ?? ''), 0, 100),
            'role' => isset(ROLE[$role]) ? $role : '',
            'status' => in_array($status, ['aktif', 'nonaktif'], true) ? $status : '',
        ];
    }

    /** Daftar pengguna terbaru dulu, dengan jumlah_percobaan (bukan 'berlangsung') dan jumlah_kuis. */
    public function search(array $f, int $limit, int $offset): Collection
    {
        return $this->filtered($f)
            ->withCount([
                'attempts as jumlah_percobaan' => fn (Builder $q) => $q->where('status', '<>', 'berlangsung'),
                'quizzes as jumlah_kuis',
            ])
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit($limit)->offset($offset)
            ->get();
    }

    public function searchCount(array $f): int
    {
        return $this->filtered($f)->count();
    }

    /** role => jumlah pengguna; role tanpa pengguna tidak ikut. */
    public function countByRole(): array
    {
        return collect(array_keys(ROLE))
            ->mapWithKeys(fn ($role) => [$role => User::where('role', $role)->count()])
            ->filter()
            ->all();
    }

    /**
     * Ubah role/status oleh admin. Null bila berhasil, atau pesan galat.
     * Admin tidak bisa mengubah akunnya sendiri atau akun admin; role admin tidak bisa diberikan lewat aplikasi.
     */
    public function adminSet(User $admin, int $targetId, string $kolom, string $nilai): ?string
    {
        $target = User::find($targetId);
        if (!$target) return 'Pengguna tidak ditemukan.';
        if ($target->is($admin)) {
            return $kolom === 'role' ? 'Kamu tidak dapat mengubah role akunmu sendiri.' : 'Kamu tidak dapat menonaktifkan akunmu sendiri.';
        }
        if ($target->role === 'admin') {
            return $kolom === 'role' ? 'Akun administrator dilindungi dan tidak dapat diubah.' : 'Akun administrator dilindungi dan tidak dapat dinonaktifkan.';
        }
        $boleh = $kolom === 'role' ? ['peserta', 'pengajar'] : ['aktif', 'nonaktif'];
        if (!in_array($nilai, $boleh, true)) return ($kolom === 'role' ? 'Role' : 'Status') . ' tidak valid.';
        $target->update([$kolom => $nilai]);
        return null;
    }

    private function filtered(array $f): Builder
    {
        return User::query()
            ->when($f['q'] ?? '', fn (Builder $q, $kata) => $q->where(fn (Builder $w) => $w
                ->where('nama', 'like', "%$kata%")->orWhere('email', 'like', "%$kata%")))
            ->when(($f['role'] ?? '') !== '' && isset(ROLE[$f['role']]), fn (Builder $q) => $q->where('role', $f['role']))
            ->when(in_array($f['status'] ?? '', ['aktif', 'nonaktif'], true), fn (Builder $q) => $q->where('status', $f['status']));
    }
}
