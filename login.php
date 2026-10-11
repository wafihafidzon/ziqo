<?php
session_start();
require 'data.php';
require 'partials/auth-ikon.php';

// Sudah login? langsung ke dashboard
if (!empty($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

$gagal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Cari user di data.json, lalu cocokkan password-nya
    $user = cariUserByEmail(loadData()['users'], $email);

    if ($user && $user['password'] === $password) {
        $_SESSION['logged_in']  = true;
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nama']  = $user['nama'];

        header('Location: index.php');
        exit;
    }
    $gagal = true;
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Masuk | ZIQO</title>
    <link rel="stylesheet" href="auth.css" />
  </head>
  <body class="ziqo-auth tema-ungu-kiri">
    <div class="auth-layout">
      <section class="auth-brand">
        <h1 class="brand-logo"><img src="assets/logo-ziqo.webp" alt="ZIQO - Zone Of Interactive Quiz &amp; Opportunity" /></h1>
        <div class="maskot-wrap">
          <p class="brand-sapa">Let&rsquo;s<br />Learn<br />Together!</p>
          <img class="brand-maskot" src="assets/maskot.webp" alt="Maskot ZIQO" onerror="this.style.display='none'" />
        </div>
      </section>

      <main class="auth-panel">
        <h1>Welcome Back!</h1>
        <p class="deskripsi">Masuk ke akun ZIQO kamu dan lanjutkan pertualangan belajarmu!</p>

        <?php if ($gagal): ?>
          <p class="pesan-error" role="alert">Email atau password belum sesuai.</p>
        <?php endif; ?>

        <form method="post" action="login.php">
          <label class="input-pil">
            <?php echo ikon('mail'); ?>
            <input name="email" type="text" placeholder="Email atau Username" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
          </label>
          <label class="input-pil">
            <?php echo ikon('lock'); ?>
            <input name="password" type="password" placeholder="Password" required />
            <button class="toggle-mata" type="button" aria-label="Tampilkan password"><?php echo ikon('mata'); ?></button>
          </label>

          <div class="baris-opsi">
            <label><input type="checkbox" name="ingat" checked /> Ingat saya</label>
            <a href="#">Lupa password?</a>
          </div>

          <button class="tombol-ungu" type="submit">Masuk</button>
          <div class="pemisah">atau</div>
          <button class="tombol-garis" type="button"><?php echo ikon('google'); ?> Masuk dengan Google</button>
        </form>

        <p class="pindah">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
      </main>
    </div>
    <script src="auth.js"></script>
  </body>
</html>
