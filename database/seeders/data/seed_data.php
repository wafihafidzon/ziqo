<?php
// Data awal untuk DatabaseSeeder. Tanpa data percobaan palsu.

$WAKTU = '2026-11-01 08:00:00';

$users = [
    // [nama, email, hash, role, telepon, jenjang]
    ['Admin ZIQO', 'admin@ziqo.id', '$2y$10$klvZiYAH1XqULp1RbjWKduGKz4/.PNlcKFudWtYphTq3r4odbkwd2', 'admin', null, null],          // admin123
    ['Bu Rina Pratiwi', 'guru@ziqo.id', '$2y$10$ANjd..Lf5K/RXQe1688xb.3z1L9YS5A1gCgZEs0BaICVDOrtFHNwW', 'pengajar', null, null], // guru1234
    ['Pak Dimas Arya', 'dimas@ziqo.id', '$2y$10$ANjd..Lf5K/RXQe1688xb.3z1L9YS5A1gCgZEs0BaICVDOrtFHNwW', 'pengajar', null, null], // guru1234
    // Akun lama dari data.json (password lama dipertahankan, kini disimpan sebagai hash)
    ['Demo ZIQO', 'demo@ziqo.id', '$2y$10$qmT5HzJdoOEjs0OMkkhFa.eCiap5Z7C.OA1uYg1ukxh9XiDchPtAK', 'peserta', null, 'sma'],    // demo1234
    ['testinguser', 'testing@mail.com', '$2y$10$CB6T6ooYJPaqu4aMxcWovOPgYz5p1Zp84vJyF85LbSW7gSx24feou', 'peserta', null, null], // testing
    ['User Ganteng Cantik', 'usergantengcantik@gmail.com', '$2y$10$m51DYpML5W4TPay3S73PSOBnAgLpfnUzx./PFXcIcz28DdbGHmDtK', 'peserta', '08123456789', null], // user123
];

$categories = [
    // nama => [warna, deskripsi, [topik...]]
    'Matematika' => ['ungu', 'Bilangan, aljabar, geometri, dan pengolahan data.', ['Aljabar', 'Geometri', 'Trigonometri', 'Statistika', 'Aritmetika']],
    'Bahasa Indonesia' => ['merah', 'Tata bahasa, ejaan, dan pemahaman bacaan.', ['Tata Bahasa', 'Ejaan (EYD)', 'Teks dan Bacaan', 'Sastra']],
    'Bahasa Inggris' => ['biru', 'Grammar, vocabulary, dan reading.', ['Grammar', 'Vocabulary', 'Reading']],
    'IPA' => ['hijau', 'Fisika, kimia, dan biologi dasar.', ['Fisika', 'Kimia', 'Biologi']],
    'IPS' => ['kuning', 'Masyarakat, ekonomi, dan lingkungan sosial.', ['Sosiologi', 'Interaksi Sosial']],
    'Informatika' => ['biru', 'Pemrograman, data, jaringan, dan keamanan.', ['Pemrograman', 'Basis Data', 'Jaringan Komputer', 'Keamanan Informasi', 'Algoritma']],
    'Sejarah' => ['oranye', 'Peristiwa penting Indonesia dan dunia.', ['Sejarah Indonesia', 'Sejarah Dunia']],
    'Geografi' => ['hijau', 'Bumi, peta, dan fenomena alam.', ['Peta dan Wilayah', 'Fenomena Alam']],
    'Ekonomi' => ['kuning', 'Kegiatan ekonomi, pasar, dan keuangan.', ['Ekonomi Mikro', 'Keuangan']],
    'Pendidikan Pancasila' => ['merah', 'Nilai Pancasila dan kewarganegaraan.', ['Nilai Pancasila', 'Konstitusi']],
    'Seni dan Budaya' => ['pink', 'Seni rupa, musik, dan budaya Nusantara.', ['Seni Rupa', 'Musik', 'Budaya Nusantara']],
    'Pengetahuan Umum' => ['ungu', 'Wawasan umum lintas bidang.', []],
];

$badges = [
    // [kode, nama, deskripsi, ikon, warna, syarat, nilai, kategori(nama)|null]
    ['first-step', 'First Step', 'Menyelesaikan kuis pertama.', 'langkah', 'hijau', 'jumlah_kuis', 1, null],
    ['consistent-learner', 'Consistent Learner', 'Menyelesaikan 5 kuis.', 'api', 'oranye', 'jumlah_kuis', 5, null],
    ['quiz-master', 'Quiz Master', 'Menyelesaikan 15 kuis.', 'mahkota', 'kuning', 'jumlah_kuis', 15, null],
    ['perfect-score', 'Perfect Score', 'Meraih nilai 100 pada sebuah kuis.', 'bintang', 'kuning', 'skor_sempurna', 1, null],
    ['math-explorer', 'Math Explorer', 'Menyelesaikan 3 kuis Matematika.', 'kompas', 'ungu', 'kuis_kategori', 3, 'Matematika'],
    ['code-explorer', 'Code Explorer', 'Menyelesaikan 3 kuis Informatika.', 'kode', 'biru', 'kuis_kategori', 3, 'Informatika'],
    ['knowledge-seeker', 'Knowledge Seeker', 'Mencoba kuis dari 3 mata pelajaran berbeda.', 'buku', 'pink', 'ragam_kategori', 3, null],
];

// Kuis: pembuat (email), kategori, topik, judul, deskripsi, kesulitan, jenjang, durasi, feedback, navigasi bebas, soal[]
// Soal: [tipe, teks, poin, pembahasan, opsi[] | jawaban[], pencocokan]
//  - pg/bs: opsi = [[teks, benar], ...]
//  - pgk:   opsi = [[teks, benar], ...] (lebih dari satu benar)
//  - isian: jawaban = ['jawaban diterima', ...]
//  - esai:  tanpa opsi
$quizzes = [
    [
        'guru@ziqo.id', 'Matematika', 'Aljabar', 'Persamaan Linear Satu Variabel',
        'Latihan menyelesaikan persamaan linear satu variabel dalam bentuk sederhana.',
        'mudah', 'smp', 10, 1, 1,
        [
            ['pg', 'Nilai x dari persamaan 2x + 6 = 14 adalah ...', 10, '2x = 14 - 6 = 8, sehingga x = 4.', [['2', 0], ['4', 1], ['6', 0], ['10', 0]]],
            ['pg', 'Jika 3x - 5 = 10, maka x = ...', 10, '3x = 15, jadi x = 5.', [['3', 0], ['5', 1], ['15', 0], ['-5', 0]]],
            ['bs', 'x = 3 adalah penyelesaian dari 5x + 2 = 17.', 10, '5(3) + 2 = 17, jadi pernyataan benar.', [['Benar', 1], ['Salah', 0]]],
            ['isian', 'Tentukan nilai a jika a/4 = 6.', 10, 'a = 6 × 4 = 24.', ['24']],
            ['pgk', 'Manakah yang termasuk persamaan linear satu variabel? (pilih semua yang benar)', 10, 'Persamaan linear satu variabel hanya memiliki satu variabel berpangkat satu.', [['x + 7 = 12', 1], ['2y = 18', 1], ['x² + 1 = 5', 0], ['x + y = 4', 0]]],
        ],
    ],
    [
        'guru@ziqo.id', 'Matematika', 'Geometri', 'Bangun Datar dan Luasnya',
        'Uji pemahamanmu tentang rumus luas dan keliling bangun datar.',
        'mudah', 'sd', 0, 1, 1,
        [
            ['pg', 'Luas persegi dengan sisi 7 cm adalah ...', 10, 'Luas persegi = s × s = 7 × 7 = 49 cm².', [['14 cm²', 0], ['28 cm²', 0], ['49 cm²', 1], ['56 cm²', 0]]],
            ['pg', 'Keliling persegi panjang dengan panjang 8 cm dan lebar 5 cm adalah ...', 10, 'K = 2 × (p + l) = 2 × 13 = 26 cm.', [['13 cm', 0], ['26 cm', 1], ['40 cm', 0], ['80 cm', 0]]],
            ['bs', 'Segitiga dengan alas 10 cm dan tinggi 6 cm memiliki luas 60 cm².', 10, 'Luas segitiga = ½ × a × t = 30 cm², jadi pernyataan salah.', [['Benar', 0], ['Salah', 1]]],
            ['isian', 'Berapa jumlah sisi pada bangun segi enam?', 10, 'Segi enam (heksagon) memiliki 6 sisi.', ['6', 'enam']],
        ],
    ],
    [
        'dimas@ziqo.id', 'Matematika', 'Statistika', 'Mean, Median, dan Modus',
        'Mengolah data tunggal: rata-rata, nilai tengah, dan nilai yang paling sering muncul.',
        'menengah', 'sma', 15, 0, 1,
        [
            ['pg', 'Rata-rata dari data 4, 6, 8, 10, 12 adalah ...', 10, 'Jumlah 40 dibagi 5 data = 8.', [['6', 0], ['8', 1], ['9', 0], ['10', 0]]],
            ['pg', 'Median dari data 3, 9, 5, 7, 1 adalah ...', 10, 'Diurutkan: 1, 3, 5, 7, 9. Nilai tengah = 5.', [['5', 1], ['7', 0], ['3', 0], ['9', 0]]],
            ['isian', 'Modus dari data 2, 4, 4, 5, 7, 4, 2 adalah ...', 10, 'Angka 4 muncul tiga kali, paling sering.', ['4']],
            ['pgk', 'Pernyataan yang benar tentang data 1, 2, 2, 3, 7? (pilih semua yang benar)', 15, 'Mean = 15/5 = 3, median = 2, modus = 2.', [['Mean = 3', 1], ['Median = 2', 1], ['Modus = 3', 0], ['Modus = 2', 1]]],
            ['esai', 'Jelaskan mengapa median lebih cocok daripada mean untuk menggambarkan data gaji karyawan di sebuah perusahaan.', 20, 'Median tidak terpengaruh nilai ekstrem (misalnya gaji direktur yang sangat tinggi), sehingga lebih mewakili mayoritas karyawan.', []],
        ],
    ],
    [
        'dimas@ziqo.id', 'Informatika', 'Basis Data', 'Dasar SQL dan Relasi Tabel',
        'Konsep primary key, foreign key, dan perintah SQL dasar.',
        'menengah', 'pt', 20, 1, 1,
        [
            ['pg', 'Perintah SQL untuk mengambil data dari tabel adalah ...', 10, 'SELECT digunakan untuk membaca data.', [['INSERT', 0], ['SELECT', 1], ['UPDATE', 0], ['DELETE', 0]]],
            ['bs', 'Primary key boleh bernilai NULL.', 10, 'Primary key harus unik dan tidak boleh NULL.', [['Benar', 0], ['Salah', 1]]],
            ['pgk', 'Manakah yang merupakan perintah DML (Data Manipulation Language)? (pilih semua yang benar)', 15, 'INSERT, UPDATE, DELETE adalah DML; CREATE TABLE termasuk DDL.', [['INSERT', 1], ['UPDATE', 1], ['CREATE TABLE', 0], ['DELETE', 1]]],
            ['isian', 'Kolom yang merujuk ke primary key tabel lain disebut ... key.', 10, 'Foreign key menghubungkan dua tabel.', ['foreign', 'foreign key']],
            ['esai', 'Jelaskan apa itu SQL injection dan bagaimana prepared statement mencegahnya.', 25, 'SQL injection terjadi ketika input pengguna disisipkan langsung ke query sehingga dapat mengubah perintah SQL. Prepared statement memisahkan struktur query dari data, sehingga input selalu diperlakukan sebagai nilai, bukan perintah.', []],
        ],
    ],
    [
        'guru@ziqo.id', 'Informatika', 'Pemrograman', 'Logika Pemrograman Dasar',
        'Variabel, percabangan, dan perulangan dalam pemrograman.',
        'mudah', 'sma', 0, 1, 1,
        [
            ['pg', 'Struktur yang digunakan untuk mengulang perintah adalah ...', 10, 'Perulangan (loop) seperti for dan while.', [['Percabangan', 0], ['Perulangan', 1], ['Variabel', 0], ['Komentar', 0]]],
            ['pg', 'Hasil dari 7 % 3 (modulus) adalah ...', 10, '7 dibagi 3 = 2 sisa 1.', [['1', 1], ['2', 0], ['3', 0], ['0', 0]]],
            ['bs', 'Dalam PHP, nama variabel diawali tanda $.', 10, 'Contoh: $nama = "Ziqo";', [['Benar', 1], ['Salah', 0]]],
            ['isian', 'Kata kunci PHP untuk menampilkan teks ke layar (4 huruf) adalah ...', 10, 'echo digunakan untuk mencetak output.', ['echo'], 'ketat'],
        ],
    ],
    [
        'guru@ziqo.id', 'Bahasa Inggris', 'Grammar', 'Simple Present vs Present Continuous',
        'Choose the correct tense in everyday sentences.',
        'menengah', 'smp', 10, 1, 0,
        [
            ['pg', 'She ___ to school every day.', 10, 'Kebiasaan sehari-hari menggunakan simple present: goes.', [['go', 0], ['goes', 1], ['is going', 0], ['going', 0]]],
            ['pg', 'Look! The children ___ in the garden now.', 10, 'Kejadian yang sedang berlangsung: are playing.', [['play', 0], ['plays', 0], ['are playing', 1], ['played', 0]]],
            ['bs', '"Water boils at 100 degrees Celsius" uses the simple present tense.', 10, 'Fakta umum menggunakan simple present.', [['Benar', 1], ['Salah', 0]]],
            ['isian', 'Complete: I am ___ (read) a novel right now.', 10, 'Present continuous: am reading.', ['reading']],
        ],
    ],
    [
        'dimas@ziqo.id', 'IPA', 'Biologi', 'Sistem Pencernaan Manusia',
        'Organ dan fungsi dalam sistem pencernaan.',
        'mudah', 'smp', 0, 1, 1,
        [
            ['pg', 'Organ tempat terjadinya penyerapan sari makanan terbanyak adalah ...', 10, 'Usus halus menyerap sebagian besar nutrisi.', [['Lambung', 0], ['Usus halus', 1], ['Usus besar', 0], ['Kerongkongan', 0]]],
            ['pg', 'Enzim pada air liur yang mengubah amilum menjadi maltosa adalah ...', 10, 'Ptialin (amilase ludah).', [['Pepsin', 0], ['Ptialin', 1], ['Lipase', 0], ['Tripsin', 0]]],
            ['pgk', 'Organ yang dilalui makanan secara langsung adalah ... (pilih semua yang benar)', 10, 'Hati dan pankreas adalah kelenjar pencernaan, tidak dilalui makanan.', [['Kerongkongan', 1], ['Lambung', 1], ['Hati', 0], ['Pankreas', 0]]],
            ['bs', 'Lambung menghasilkan asam klorida (HCl).', 10, 'HCl membunuh kuman dan mengaktifkan pepsinogen.', [['Benar', 1], ['Salah', 0]]],
        ],
    ],
    [
        'guru@ziqo.id', 'Sejarah', 'Sejarah Indonesia', 'Proklamasi Kemerdekaan',
        'Peristiwa seputar proklamasi 17 Agustus 1945.',
        'mudah', 'smp', 0, 0, 1,
        [
            ['pg', 'Teks proklamasi diketik oleh ...', 10, 'Sayuti Melik mengetik naskah proklamasi.', [['Soekarno', 0], ['Mohammad Hatta', 0], ['Sayuti Melik', 1], ['Ahmad Soebardjo', 0]]],
            ['pg', 'Peristiwa pengamanan Soekarno-Hatta sebelum proklamasi dikenal sebagai Peristiwa ...', 10, 'Peristiwa Rengasdengklok, 16 Agustus 1945.', [['Rengasdengklok', 1], ['Bandung Lautan Api', 0], ['Lengkong', 0], ['Madiun', 0]]],
            ['isian', 'Pada tahun berapa Indonesia memproklamasikan kemerdekaannya?', 10, '17 Agustus 1945.', ['1945']],
            ['bs', 'Proklamasi dibacakan di Jalan Pegangsaan Timur No. 56, Jakarta.', 10, 'Rumah Soekarno di Pegangsaan Timur 56.', [['Benar', 1], ['Salah', 0]]],
        ],
    ],
    [
        'dimas@ziqo.id', 'Geografi', 'Fenomena Alam', 'Lapisan Bumi dan Gempa',
        'Struktur lapisan bumi dan penyebab gempa tektonik.',
        'sulit', 'sma', 12, 0, 1,
        [
            ['pg', 'Lapisan bumi paling luar disebut ...', 10, 'Kerak bumi (litosfer bagian atas).', [['Mantel', 0], ['Kerak', 1], ['Inti luar', 0], ['Inti dalam', 0]]],
            ['pg', 'Gempa yang disebabkan pergerakan lempeng disebut gempa ...', 10, 'Gempa tektonik.', [['Vulkanik', 0], ['Runtuhan', 0], ['Tektonik', 1], ['Imbasan', 0]]],
            ['pgk', 'Lempeng yang bertemu di sekitar wilayah Indonesia adalah ... (pilih semua yang benar)', 15, 'Indonesia berada di pertemuan lempeng Indo-Australia, Eurasia, dan Pasifik.', [['Indo-Australia', 1], ['Eurasia', 1], ['Pasifik', 1], ['Afrika', 0]]],
            ['esai', 'Jelaskan mengapa Indonesia rawan gempa dan berikan satu upaya mitigasinya.', 20, 'Karena berada di pertemuan tiga lempeng aktif dan cincin api Pasifik. Mitigasi: bangunan tahan gempa, simulasi evakuasi, sistem peringatan dini.', []],
        ],
    ],
    [
        'guru@ziqo.id', 'Pengetahuan Umum', null, 'Wawasan Nusantara Seru',
        'Campuran pertanyaan ringan tentang Indonesia.',
        'mudah', 'umum', 5, 1, 1,
        [
            ['pg', 'Ibu kota Provinsi Jawa Barat adalah ...', 10, 'Bandung.', [['Bandung', 1], ['Bogor', 0], ['Cirebon', 0], ['Bekasi', 0]]],
            ['bs', 'Komodo adalah hewan endemik Pulau Sumatra.', 10, 'Komodo hidup di Pulau Komodo dan Flores, NTT.', [['Benar', 0], ['Salah', 1]]],
            ['isian', 'Lagu kebangsaan Indonesia berjudul ...', 10, 'Indonesia Raya, karya W.R. Supratman.', ['Indonesia Raya']],
            ['pgk', 'Manakah yang merupakan tarian tradisional Indonesia? (pilih semua yang benar)', 10, 'Saman (Aceh), Kecak (Bali), Jaipong (Jawa Barat). Flamenco berasal dari Spanyol.', [['Saman', 1], ['Kecak', 1], ['Flamenco', 0], ['Jaipong', 1]]],
        ],
    ],
    [
        'guru@ziqo.id', 'Bahasa Indonesia', 'Ejaan (EYD)', 'Huruf Kapital dan Tanda Baca',
        'Penggunaan huruf kapital dan tanda baca sesuai EYD.',
        'menengah', 'sma', 0, 1, 1,
        [
            ['pg', 'Penulisan yang benar adalah ...', 10, 'Nama hari ditulis dengan huruf kapital: Senin.', [['hari senin', 0], ['Hari senin', 0], ['hari Senin', 1], ['HARI SENIN', 0]]],
            ['bs', 'Tanda koma dipakai sebelum kata "tetapi" dalam kalimat majemuk setara.', 10, 'Contoh: Saya ingin datang, tetapi hujan turun.', [['Benar', 1], ['Salah', 0]]],
            ['isian', 'Tanda baca yang dipakai di akhir kalimat tanya adalah tanda ...', 10, 'Tanda tanya (?).', ['tanya', 'tanda tanya', '?']],
        ],
    ],
];
