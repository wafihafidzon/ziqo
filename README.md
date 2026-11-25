# ZIQO: Zone of Interactive Quiz & Opportunity

Platform kuis edukasi, versi **Laravel 13** dari aplikasi ZIQO native PHP. Fitur, tampilan, dan aturan penilaian sama dengan versi native.

## Kebutuhan

- PHP 8.3+ dengan ekstensi `pdo_mysql` / `pdo_sqlite`, `gd`, `fileinfo`, `mbstring`
- Composer
- MySQL 8 / MariaDB 10.3+, atau SQLite (bawaan)

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Atau cukup `composer setup`. Dengan Laravel Herd, folder ini langsung bisa dibuka di `http://ziqo-laravel.test`.

### Memakai MySQL (MAMP/XAMPP/Herd)

Buat database `ziqo` (collation `utf8mb4_unicode_ci`), lalu ubah `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306        # MAMP: 8889
DB_DATABASE=ziqo
DB_USERNAME=root
DB_PASSWORD=        # MAMP: root
```

Lalu jalankan `php artisan migrate --seed`.

## Akun demo

| Role | Email | Password |
|---|---|---|
| Administrator | admin@ziqo.id | admin123 |
| Pengajar | guru@ziqo.id | guru1234 |
| Pengajar | dimas@ziqo.id | guru1234 |
| Peserta | demo@ziqo.id | demo1234 |
| Peserta | usergantengcantik@gmail.com | user123 |
| Peserta | testing@mail.com | testing |

## Struktur

```
routes/web.php                     Semua URL + middleware auth/role
app/Http/Controllers/              Publik, auth, peserta (Quiz, Play, Student, Profile, Dashboard)
  Pengajar/                        Kuis saya, soal, hasil, penilaian esai
  Admin/                           Pengguna, kategori & topik, pantau kuis
app/Http/Middleware/               EnsureRole (role:pengajar,admin), EnsureUserIsActive
app/Models/                        Eloquent (relasi, cast, scope): User, Category, Subcategory, Quiz,
                                   Question, AnswerOption, Attempt, UserAnswer, Badge
app/Services/                      Logika bisnis: QuizService, QuestionService, AttemptService (penilaian),
                                   BadgeService, StatsService, UserService
app/Support/                       Chart (grafik SVG), Avatar (upload foto)
app/helpers.php                    Label, format tanggal/angka, ikon SVG, helper form
resources/views/                   Blade: layouts, partials, halaman peserta/pengajar/admin
public/assets/                     CSS & JS ZIQO (tanpa build tool)
database/migrations, seeders/      Skema tabel + data awal (seeders/data/seed_data.php)
tests/Feature/                     Uji alur utama + per domain (Kuis, Pengerjaan, Pengguna)
```

## Peta URL (native → Laravel)

| Native | Laravel |
|---|---|
| `index.php` | `/` |
| `login.php`, `register.php`, `logout.php` | `/login`, `/register`, `POST /logout` |
| `jelajah.php`, `kuis.php?id=1` | `/jelajah`, `/kuis/1` |
| `main.php?a=5`, `hasil.php?a=5` | `/kerjakan/5`, `/hasil/5` |
| `dashboard.php`, `riwayat.php`, `pencapaian.php`, `profil.php` | `/dashboard`, `/riwayat`, `/pencapaian`, `/profil` |
| `pengajar/kuis.php`, `pengajar/kuis-form.php?id=1` | `/pengajar/kuis`, `/pengajar/kuis/1/edit` (baru: `/pengajar/kuis/buat`) |
| `pengajar/soal.php?kuis=1`, `pengajar/soal-form.php?kuis=1&id=2` | `/pengajar/kuis/1/soal`, `/pengajar/kuis/1/soal/2/edit` |
| `pengajar/hasil.php?kuis=1`, `pengajar/nilai.php?a=5`, `pengajar/penilaian.php` | `/pengajar/kuis/1/hasil`, `/pengajar/nilai/5`, `/pengajar/penilaian` |
| `admin/pengguna.php`, `admin/pengguna-detail.php?id=4` | `/admin/pengguna`, `/admin/pengguna/4` |
| `admin/kategori.php`, `admin/kuis.php` | `/admin/kategori`, `/admin/kuis` |

## Yang diganti oleh Laravel

- Koneksi PDO & config → `.env` + query builder Laravel.
- Sesi, login, logout, CSRF → session guard + middleware `auth`, `guest`, `VerifyCsrfToken`.
- Cek role manual → middleware `role:...`; status nonaktif dicek tiap request.
- Validator buatan sendiri → Laravel Validator dengan pesan bahasa Indonesia (`lang/id/validation.php`).
- `install.php` + `seed.sql` → migration + seeder.
- Upload avatar → disk `public` (`storage/app/public/avatars`).

## Pengujian

```bash
php artisan test
```

Aturan bisnis lain (penilaian, reset riwayat, badge, hak akses) sama dengan versi native; lihat `CONTEXT.md`.
