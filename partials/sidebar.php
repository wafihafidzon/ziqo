<?php
// Menu dengan '#' masih placeholder (halamannya belum ada)
// Set $halaman sebelum include, contoh: $halaman = 'dashboard';
require_once __DIR__ . '/ikon-app.php';
$halaman = $halaman ?? '';
$menu = [
    'dashboard'  => ['index.php',      'Dashboard',  'home'],
    'quiz'       => ['#',              'Quiz',       'quiz'],
    'room'       => ['#',              'Room',       'room'],
    'kategori'   => ['#',              'Kategori',   'kategori'],
    'pertanyaan' => ['#',              'Pertanyaan', 'pertanyaan'],
    'pengguna'   => ['#',              'Pengguna',   'pengguna'],
    'profil'     => ['profil.php',     'Profil',     'profil'],
];
?>
<link rel="stylesheet" href="dashboard.css" />
<aside class="sidebar">
  <a class="sidebar-logo" href="index.php">
    <img src="assets/logo-ziqo.webp" alt="ZIQO - Zone Of Interactive Quiz &amp; Opportunity" />
  </a>
  <nav class="sidebar-nav">
    <?php foreach ($menu as $kunci => [$url, $label, $ikon]): ?>
      <a class="nav-link<?php echo $halaman === $kunci ? ' active' : ''; ?>" href="<?php echo $url; ?>">
        <?php echo ikonApp($ikon); ?><span><?php echo $label; ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <hr class="sidebar-garis" />
  <a class="nav-link logout-link" href="logout.php"><?php echo ikonApp('logout'); ?><span>Log Out</span></a>
  <div class="sidebar-maskot"><img src="assets/maskot.webp" alt="" onerror="this.style.display='none'" /></div>
</aside>
