# ZIQO: Glosarium Domain

Zone of Interactive Quiz & Opportunity. Istilah di bawah dipakai sama di kode, laporan, dan diskusi kelompok.

- **Pengguna**: pemilik akun ZIQO, dengan satu **Role**: Peserta, Pengajar, atau Administrator. Akun baru selalu Peserta; hanya Administrator yang dapat mengubah role (Peserta ↔ Pengajar) dan **Status** akun (aktif/nonaktif). Akun Administrator dilindungi dari perubahan lewat aplikasi.
- **Kategori**: mata pelajaran (Matematika, Informatika, ...), dikelola Administrator di database.
- **Topik**: subkategori milik satu Kategori (mis. Aljabar pada Matematika).
- **Quiz**: kumpulan Pertanyaan buatan Pengajar, dengan Kategori, Topik (opsional), **Tingkat kesulitan** (Mudah/Menengah/Sulit), **Jenjang** (SD/SMP/SMA-SMK/Perguruan Tinggi/Umum), batas waktu opsional, mode umpan balik, dan mode navigasi. Berstatus Draft atau Terbit.
- **Pertanyaan**: satu soal dengan **Jenis soal**: Pilihan Ganda, Pilihan Ganda Kompleks, Benar/Salah, Isian Singkat, atau Esai.
- **Opsi jawaban**: pilihan pada PG/PGK/BS; untuk Isian Singkat berisi variasi jawaban yang diterima.
- **Percobaan**: satu kali Pengguna mengerjakan Quiz. Status: berlangsung → menunggu (ada esai belum dinilai) → selesai.
- **Skor**: poin didapat ÷ poin maksimal × 100. Tanpa bonus kecepatan.
- **XP**: jumlah poin dari Percobaan selesai yang belum diarsipkan.
- **Badge**: pencapaian yang diberikan server saat syarat terpenuhi; permanen.
- **Reset riwayat**: pengarsipan seluruh Percobaan milik sendiri; tidak menghapus data.

## Belum diputuskan

- Room (sesi dengan Kode Akses) dari rencana awal: belum diimplementasikan.
