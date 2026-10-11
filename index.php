<?php
require 'partials/auth.php';
require 'data.php';
require 'partials/ikon-app.php';

$data    = loadData();
$halaman = 'dashboard';

// Peta id kategori => nama
$namaKategori = [];
foreach ($data['categories'] as $k) {
    $namaKategori[$k['id']] = $k['nama'] ?? '-';
}

// Jumlah soal per quiz
$jumlahSoal = [];
foreach ($data['questions'] as $q) {
    $id = $q['quiz_id'] ?? null;
    $jumlahSoal[$id] = ($jumlahSoal[$id] ?? 0) + 1;
}

// 5 quiz terbaru (id terbesar di atas)
$quizTerbaru = $data['quizzes'];
usort($quizTerbaru, fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));
$quizTerbaru = array_slice($quizTerbaru, 0, 5);

$statistik = [
    ['ungu',   'quiz',       count($data['quizzes']),   'Total Quiz',       'quiz.php'],
    ['kuning', 'room',       count($data['rooms']),     'Total Room',       'room.php'],
    ['hijau',  'kategori',   count($data['categories']),'Total Kategori',   'kategori.php'],
    ['biru',   'pertanyaan', count($data['questions']), 'Total Pertanyaan', 'pertanyaan.php'],
];
$warnaBadge = ['ungu', 'kuning', 'hijau', 'biru', 'pink'];
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard | ZIQO</title>
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body class="dashboard-body">
    <?php include 'partials/sidebar.php'; ?>

    <main class="dashboard-main">
      <?php include 'partials/topbar.php'; ?>

      <section class="stats-grid">
        <?php foreach ($statistik as [$warna, $ikon, $jumlah, $label]): ?>
          <div class="stat-card stat-<?php echo $warna; ?>">
            <span class="stat-ikon"><?php echo ikonApp($ikon); ?></span>
            <span class="stat-panah"><?php echo ikonApp('panah-kanan'); ?></span>
            <p class="stat-number"><?php echo $jumlah; ?></p>
            <p class="stat-label"><?php echo $label; ?></p>
          </div>
        <?php endforeach; ?>
      </section>

      <h2 class="judul-bagian"><?php echo ikonApp('quiz'); ?> Quiz Terbaru</h2>
      <section class="tabel-kartu">
        <div class="tabel-scroll">
          <table class="tabel-quiz">
            <thead>
              <tr><th>Judul Quiz</th><th>Kategori</th><th>Jumlah Soal</th><th>Tanggal Dibuat</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php if (!$quizTerbaru): ?>
                <tr><td class="kosong" colspan="5">Belum ada quiz.</td></tr>
              <?php endif; ?>
              <?php foreach ($quizTerbaru as $quiz):
                $kid   = $quiz['kategori_id'] ?? null;
                $nama  = $namaKategori[$kid] ?? '-';
                $tgl   = $quiz['dibuat_pada'] ?? $quiz['tanggal_dibuat'] ?? '';
                $tglFmt = $tgl ? date('d M Y', strtotime($tgl)) : '-';
              ?>
                <tr>
                  <td class="kiri"><?php echo htmlspecialchars($quiz['judul'] ?? '-'); ?></td>
                  <td><span class="badge badge-<?php echo $warnaBadge[((int) $kid) % count($warnaBadge)]; ?>"><?php echo htmlspecialchars($nama); ?></span></td>
                  <td><?php echo $jumlahSoal[$quiz['id'] ?? null] ?? 0; ?></td>
                  <td><?php echo $tglFmt; ?></td>
                  <td><span class="tombol-mata"><?php echo ikonApp('mata'); ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </body>
</html>
