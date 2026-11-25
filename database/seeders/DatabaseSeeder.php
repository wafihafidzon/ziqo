<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        require __DIR__ . '/data/seed_data.php';

        // Akun demo (password-nya tercantum di README) dan kuis milik akun itu hanya untuk lokal/testing.
        $demo = app()->environment(['local', 'testing']);

        DB::transaction(function () use ($WAKTU, $users, $categories, $badges, $quizzes, $demo) {
            $userId = [];
            foreach ($demo ? $users : [] as [$nama, $email, $hash, $role, $tel, $jenjang]) {
                $userId[$email] = DB::table('users')->insertGetId([
                    'nama' => $nama, 'email' => $email, 'password_hash' => $hash, 'role' => $role,
                    'status' => 'aktif', 'telepon' => $tel, 'jenjang' => $jenjang, 'created_at' => $WAKTU,
                ]);
            }

            $catId = [];
            $subId = [];
            foreach ($categories as $nama => [$warna, $desk, $topik]) {
                $catId[$nama] = DB::table('categories')->insertGetId([
                    'nama' => $nama, 'slug' => Category::slugify($nama), 'warna' => $warna,
                    'deskripsi' => $desk, 'created_at' => $WAKTU,
                ]);
                foreach ($topik as $t) {
                    $subId[$nama][$t] = DB::table('subcategories')->insertGetId(['category_id' => $catId[$nama], 'nama' => $t]);
                }
            }

            foreach ($badges as [$kode, $nama, $desk, $ikon, $warna, $syarat, $nilai, $kat]) {
                DB::table('badges')->insert([
                    'kode' => $kode, 'nama' => $nama, 'deskripsi' => $desk, 'ikon' => $ikon, 'warna' => $warna,
                    'syarat' => $syarat, 'nilai_syarat' => $nilai, 'category_id' => $kat ? $catId[$kat] : null,
                ]);
            }

            foreach ($demo ? $quizzes : [] as $k =>[$email, $kat, $sub, $judul, $desk, $sulit, $jenjang, $durasi, $fb, $nav, $soal]) {
                $quizId = DB::table('quizzes')->insertGetId([
                    'user_id' => $userId[$email], 'category_id' => $catId[$kat],
                    'subcategory_id' => $sub ? $subId[$kat][$sub] : null, 'judul' => $judul, 'deskripsi' => $desk,
                    'kesulitan' => $sulit, 'jenjang' => $jenjang, 'durasi_menit' => $durasi, 'tampilkan_feedback' => $fb,
                    'navigasi_bebas' => $nav, 'status' => 'terbit',
                    'created_at' => date('Y-m-d H:i:s', strtotime($WAKTU) + $k * 3600 * 7),
                ]);
                foreach ($soal as $u => $row) {
                    [$tipe, $teks, $poin, $bahas] = $row;
                    $qid = DB::table('questions')->insertGetId([
                        'quiz_id' => $quizId, 'tipe' => $tipe, 'teks' => $teks, 'poin' => $poin,
                        'pembahasan' => $bahas, 'pencocokan' => $row[5] ?? 'longgar', 'urutan' => $u + 1,
                    ]);
                    foreach ($row[4] ?? [] as $o => $isi) {
                        [$t, $benar] = $tipe === 'isian' ? [$isi, 1] : $isi;
                        DB::table('answer_options')->insert(['question_id' => $qid, 'teks' => $t, 'benar' => $benar, 'urutan' => $o + 1]);
                    }
                }
            }
        });

        // Admin dari env (ADMIN_EMAIL/ADMIN_PASSWORD); di luar lokal inilah satu-satunya admin awal.
        $this->call(AdminSeeder::class);
    }
}
