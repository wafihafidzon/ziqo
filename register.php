<?php
require 'data.php';
require 'partials/auth-ikon.php';

$error  = null;
$sukses = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    $data = loadData();

    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (cariUserByEmail($data['users'], $email)) {
        $error = 'Email sudah terdaftar. Silakan masuk.';
    } else {
        $data['users'][] = [
            'id'       => $data['next_id']['users']++,
            'nama'     => $nama,
            'email'    => $email,
            'password' => $password,
        ];
        saveData($data);
        $sukses = true;
    }
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daftar | ZIQO</title>
    <link rel="stylesheet" href="auth.css" />
  </head>
  <body class="ziqo-auth tema-kuning-kiri">
    <div class="auth-layout">
      <section class="auth-brand">
        <h1 class="brand-logo"><img src="assets/logo-ziqo.webp" alt="ZIQO - Zone Of Interactive Quiz &amp; Opportunity" /></h1>
        <div class="maskot-wrap">
          <img class="brand-maskot" src="assets/maskot.webp" alt="Maskot ZIQO" onerror="this.style.display='none'" />
        </div>
      </section>

      <main class="auth-panel">
        <?php if ($sukses): ?>
          <h1>Berhasil!</h1>
          <p class="deskripsi">Akun sudah dibuat. Silakan masuk dengan email dan password yang didaftarkan.</p>
          <a class="tombol-ungu" href="login.php">Masuk sekarang</a>
        <?php else: ?>
          <h1>Daftar Akun</h1>
          <p class="deskripsi">Buat akun baru untuk mulai belajar dan jelajahi berbagai kuis menarik!</p>

          <?php if ($error): ?>
            <p class="pesan-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
          <?php endif; ?>

          <form method="post" action="register.php">
            <label class="input-pil">
              <?php echo ikon('user'); ?>
              <input name="nama" placeholder="Masukan nama lengkap" required
                     value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>" />
            </label>
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
            <label class="input-pil">
              <?php echo ikon('lock'); ?>
              <input name="konfirmasi" type="password" placeholder="Konfirmasi Password" required />
              <button class="toggle-mata" type="button" aria-label="Tampilkan password"><?php echo ikon('mata'); ?></button>
            </label>

            <div class="spacer-daftar"></div>
            <button class="tombol-ungu" type="submit">Daftar</button>
          </form>

          <p class="pindah">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
        <?php endif; ?>
      </main>
    </div>
    <script src="auth.js"></script>
  </body>
</html>
