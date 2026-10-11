<?php
require 'partials/auth.php';
require 'data.php';

$halaman = 'profil';
$sukses  = false;
$data    = loadData();

// SIMPAN perubahan profil
if (isset($_POST['simpan'])) {
    foreach ($data['users'] as $i => $u) {
        if ($u['id'] == $_SESSION['user_id']) {
            $data['users'][$i]['nama'] = trim($_POST['nama']);
            $data['users'][$i]['telepon'] = trim($_POST['telepon'] ?? '');
            if (($_POST['password_baru'] ?? '') !== '') {
                $data['users'][$i]['password'] = $_POST['password_baru'];
            }
            $_SESSION['user_nama'] = $data['users'][$i]['nama'];
            saveData($data);
            $sukses = true;
        }
    }
}

$user = cariUserByEmail($data['users'], $_SESSION['user_email']);
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil | ZIQO</title>
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body class="dashboard-body">
    <?php include 'partials/sidebar.php'; ?>

    <main class="dashboard-main">
      <?php include 'partials/topbar.php'; ?>

      <h1 class="judul-halaman">Profile Saya</h1>

      <div class="profil-grid">
        <section class="profil-kartu profil-ringkas">
          <div class="profil-sampul"></div>
          <div class="profil-avatar">
            <img src="assets/maskot.webp" alt="" onerror="this.style.display='none'" />
            <span class="profil-kamera" title="Ganti foto"><?php echo ikonApp('kamera'); ?></span>
          </div>
          <h2><?php echo htmlspecialchars($user['nama']); ?></h2>
          <p class="profil-email"><?php echo htmlspecialchars($user['email']); ?></p>
          <span class="badge badge-ungu">Anggota</span>
        </section>

        <section class="profil-kartu profil-info">
          <form method="post" id="form-profil">
            <div class="profil-info-kepala">
              <h2>Informasi Profil</h2>
              <button class="tombol-edit" type="button" id="tombol-edit">Edit</button>
            </div>

            <?php if ($sukses): ?>
              <p class="pesan-sukses" role="status">Profil berhasil diperbarui.</p>
            <?php endif; ?>

            <label class="profil-label" for="nama">Nama Lengkap</label>
            <input class="profil-input" id="nama" name="nama" required readonly
                   value="<?php echo htmlspecialchars($user['nama']); ?>" />

            <label class="profil-label" for="email">Email</label>
            <input class="profil-input" id="email" type="email" readonly
                   value="<?php echo htmlspecialchars($user['email']); ?>" />

            <label class="profil-label" for="telepon">No. Telephone</label>
            <input class="profil-input" id="telepon" name="telepon" type="tel" readonly
                   value="<?php echo htmlspecialchars($user['telepon'] ?? ''); ?>" />

            <div class="profil-edit-saja" hidden>
              <label class="profil-label" for="password_baru">Password Baru (kosongkan jika tidak diubah)</label>
              <input class="profil-input" id="password_baru" name="password_baru" type="password" />
              <button class="tombol-simpan" type="submit" name="simpan">Simpan Perubahan</button>
            </div>
          </form>
        </section>
      </div>
    </main>

    <script>
      // Tombol Edit: buka kunci kolom yang boleh diubah (email tidak bisa diubah)
      const tombolEdit = document.getElementById('tombol-edit');
      tombolEdit.addEventListener('click', () => {
        const form = document.getElementById('form-profil');
        const mulai = !form.classList.contains('mode-edit');
        form.classList.toggle('mode-edit', mulai);
        form.querySelector('.profil-edit-saja').hidden = !mulai;
        ['nama', 'telepon'].forEach((id) => { document.getElementById(id).readOnly = !mulai; });
        tombolEdit.textContent = mulai ? 'Batal' : 'Edit';
        if (mulai) document.getElementById('nama').focus();
      });
    </script>
  </body>
</html>
