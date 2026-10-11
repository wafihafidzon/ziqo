<?php require_once __DIR__ . '/ikon-app.php'; ?>
<header class="topbar">
  <form class="cari" action="#" role="search">
    <?php echo ikonApp('cari'); ?>
    <input type="search" placeholder="Cari di ZIQO..." aria-label="Cari di ZIQO" />
  </form>
  <button class="tombol-lonceng" type="button" aria-label="Notifikasi"><?php echo ikonApp('lonceng'); ?></button>
  <a class="pil-user" href="profil.php">
    <img src="assets/maskot.webp" alt="" onerror="this.style.display='none'" />
    <span><?php echo htmlspecialchars($_SESSION['user_nama']); ?></span>
    <?php echo ikonApp('panah-bawah'); ?>
  </a>
</header>
