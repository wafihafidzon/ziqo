# ZIQO

PHP native + CSS native. Data disimpan di `data.json` (tanpa database).

## Menjalankan

Salin atau clone folder ini ke `htdocs` MAMP, lalu buka `http://localhost/<path>/ziqo/`.
Atau dari terminal: `php -S localhost:8000` di dalam folder `ziqo/`.

Akun demo: `demo@ziqo.id` / `demo123`, atau `UserGantengCantik@gmail.com` / `user123`

## Struktur

- `data.php`: `loadData()` / `saveData()` dan helper. Dipakai semua halaman.
- `data.json`: satu file untuk semua entitas (`users`, `categories`, `quizzes`, `questions`, `rooms`, `attempts`). Tiap entitas punya counter ID di `next_id`.
- `partials/auth.php`: taruh di baris pertama halaman yang wajib login.
- `partials/sidebar.php`: set `$halaman = '...'` sebelum `include`.

## Pembagian

| Inisial | Halaman | Entitas di `data.json` |
|---|---|---|
| MNH | `room.php` | `rooms` |
| SSM | `quiz.php` | `quizzes` |
| WU | `pertanyaan.php` | `questions` |
| LEP | `kategori.php` | `categories` |
| AMK | login, register, profil | `users` |

Pembagian Login, Register, Dashboard, dan Profile untuk laporan 3.1 belum ditetapkan.

## Alur Git

Satu branch per fitur (`fitur/room`, `fitur/quiz`, `fitur/pertanyaan`, `fitur/kategori`, `fitur/auth`), lalu merge ke `main` lewat Pull Request. Kerjakan hanya file milik fiturmu supaya tidak konflik; `data.json` jangan di-commit dengan data uji pribadi.
