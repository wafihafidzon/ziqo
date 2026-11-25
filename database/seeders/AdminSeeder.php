<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Membuat akun admin dari ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_NAMA (config app.admin).
 * Admin tidak bisa diberikan lewat aplikasi, jadi ini jalur resmi membuat admin di luar lokal.
 * Dilewati bila env kosong; akun dengan email yang sudah ada tidak pernah ditimpa.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = mb_strtolower(trim((string) config('app.admin.email')));
        $password = (string) config('app.admin.password');
        $nama = trim((string) config('app.admin.nama')) ?: 'Administrator';

        if ($email === '' || $password === '') {
            $this->command?->warn('AdminSeeder dilewati: ADMIN_EMAIL dan ADMIN_PASSWORD belum diisi.');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('ADMIN_EMAIL bukan alamat email yang valid.');
        }
        if (mb_strlen($password) < 8) {
            throw new RuntimeException('ADMIN_PASSWORD minimal 8 karakter.');
        }
        if (DB::table('users')->where('email', $email)->exists()) {
            $this->command?->warn("AdminSeeder dilewati: akun {$email} sudah ada (tidak ditimpa).");
            return;
        }

        DB::table('users')->insert([
            'nama' => mb_substr($nama, 0, 100), 'email' => $email, 'password_hash' => Hash::make($password),
            'role' => 'admin', 'status' => 'aktif', 'created_at' => waktu(),
        ]);
        $this->command?->info("Akun admin {$email} dibuat.");
    }
}
