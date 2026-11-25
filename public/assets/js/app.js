// ZIQO: interaksi umum di semua halaman (tanpa library).
(function () {
  'use strict';
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));

  /* ---------- Sidebar (mobile) ---------- */
  const sidebar = $('#sidebar');
  const tombolMenu = $('[data-buka-sidebar]');
  let tirai = null;
  function tutupSidebar() {
    sidebar?.classList.remove('terbuka');
    tombolMenu?.setAttribute('aria-expanded', 'false');
    tirai?.remove(); tirai = null;
  }
  tombolMenu?.addEventListener('click', () => {
    sidebar.classList.add('terbuka');
    tombolMenu.setAttribute('aria-expanded', 'true');
    tirai = document.createElement('div');
    tirai.className = 'sidebar-tirai';
    tirai.addEventListener('click', tutupSidebar);
    document.body.appendChild(tirai);
  });

  /* ---------- Menu pengguna ---------- */
  const tombolUser = $('[data-toggle-menu]');
  const dropdown = $('#user-dropdown');
  tombolUser?.addEventListener('click', (e) => {
    e.stopPropagation();
    const buka = dropdown.hidden;
    dropdown.hidden = !buka;
    tombolUser.setAttribute('aria-expanded', String(buka));
  });
  document.addEventListener('click', (e) => {
    if (dropdown && !dropdown.hidden && !dropdown.contains(e.target)) {
      dropdown.hidden = true; tombolUser.setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { if (dropdown) dropdown.hidden = true; tutupSidebar(); }
  });

  /* ---------- Toast ---------- */
  function hapusToast(t) { t.classList.add('keluar'); setTimeout(() => t.remove(), 300); }
  $$('.toast').forEach((t) => {
    t.querySelector('[data-tutup-toast]')?.addEventListener('click', () => hapusToast(t));
    setTimeout(() => hapusToast(t), 6000);
  });
  window.ziqoToast = function (pesan, tipe = 'info') {
    const wadah = $('.toasts');
    const t = document.createElement('div');
    t.className = 'toast toast-' + tipe;
    t.setAttribute('role', tipe === 'error' ? 'alert' : 'status');
    t.textContent = pesan;
    wadah.appendChild(t);
    setTimeout(() => hapusToast(t), 5000);
  };

  /* ---------- Lihat / sembunyikan password ---------- */
  $$('.toggle-mata').forEach((b) => b.addEventListener('click', () => {
    const input = b.parentElement.querySelector('input');
    const tampil = input.type === 'password';
    input.type = tampil ? 'text' : 'password';
    b.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
    b.setAttribute('aria-pressed', String(tampil));
  }));

  /* ---------- Dialog konfirmasi ----------
     <form data-confirm="Pesan" data-confirm-judul="Judul" data-confirm-ok="Hapus" data-bahaya> */
  const dialog = $('#dialog-konfirmasi');
  window.ziqoKonfirmasi = function ({ judul = 'Yakin?', pesan = '', ok = 'Ya, lanjutkan', bahaya = false }) {
    return new Promise((resolve) => {
      if (!dialog || typeof dialog.showModal !== 'function') { resolve(window.confirm(pesan || judul)); return; }
      $('#dk-judul').textContent = judul;
      $('#dk-pesan').textContent = pesan;
      const tombolOk = $('#dk-ok');
      tombolOk.textContent = ok;
      tombolOk.className = 'btn' + (bahaya ? ' btn-merah' : '');
      $('#dk-ikon').classList.toggle('bahaya', bahaya);
      const selesai = (hasil) => { dialog.close(); resolve(hasil); };
      tombolOk.onclick = () => selesai(true);
      dialog.querySelector('[data-batal]').onclick = () => selesai(false);
      dialog.oncancel = (e) => { e.preventDefault(); selesai(false); };
      dialog.showModal();
      tombolOk.focus();
    });
  };
  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (form.dataset.confirm && !form.dataset.terkonfirmasi) {
      e.preventDefault();
      const ok = await window.ziqoKonfirmasi({
        judul: form.dataset.confirmJudul || 'Yakin?',
        pesan: form.dataset.confirm,
        ok: form.dataset.confirmOk || 'Ya, lanjutkan',
        bahaya: 'bahaya' in form.dataset,
      });
      if (ok) {
        form.dataset.terkonfirmasi = '1';
        if (e.submitter && e.submitter.name) {
          const h = document.createElement('input');
          h.type = 'hidden'; h.name = e.submitter.name; h.value = e.submitter.value;
          form.appendChild(h);
        }
        form.requestSubmit ? form.requestSubmit() : form.submit();
      }
      return;
    }
    // Loading state: cegah klik ganda
    if (!form.hasAttribute('data-tanpa-loading')) {
      const btn = e.submitter || form.querySelector('[type=submit]');
      if (btn && form.method.toLowerCase() === 'post') { setTimeout(() => btn.classList.add('is-loading'), 0); }
    }
  }, true);

  /* ---------- Validasi sisi klien (pelengkap; server tetap memvalidasi) ---------- */
  $$('form[data-validasi]').forEach((form) => {
    form.setAttribute('novalidate', '');
    const tampilkan = (input) => {
      let pesan = '';
      const sama = input.dataset.sama && form.querySelector('[name="' + input.dataset.sama + '"]');
      if (input.validity.valueMissing) pesan = (input.dataset.label || 'Kolom ini') + ' wajib diisi.';
      else if (input.validity.typeMismatch) pesan = 'Format ' + (input.dataset.label || 'isian').toLowerCase() + ' tidak valid.';
      else if (input.validity.tooShort) pesan = (input.dataset.label || 'Isian') + ' minimal ' + input.minLength + ' karakter.';
      else if (input.validity.patternMismatch) pesan = input.title || 'Format tidak sesuai.';
      else if (input.validity.rangeOverflow || input.validity.rangeUnderflow) pesan = 'Nilai harus antara ' + input.min + ' dan ' + input.max + '.';
      else if (sama && input.value !== sama.value) pesan = (input.dataset.label || 'Konfirmasi') + ' tidak sama.';
      const field = input.closest('.field');
      let el = field?.querySelector('.pesan-salah.js');
      if (pesan) {
        if (!el && field) { el = document.createElement('div'); el.className = 'pesan-salah js'; field.appendChild(el); }
        if (el) el.textContent = pesan;
        input.setAttribute('aria-invalid', 'true');
        field?.classList.add('salah');
      } else {
        el?.remove(); input.removeAttribute('aria-invalid'); field?.classList.remove('salah');
      }
      return !pesan;
    };
    $$('input, select, textarea', form).forEach((i) => {
      i.addEventListener('blur', () => { if (i.value !== '' || i.dataset.disentuh) tampilkan(i); i.dataset.disentuh = '1'; });
      i.addEventListener('input', () => { if (i.getAttribute('aria-invalid')) tampilkan(i); });
    });
    form.addEventListener('submit', (e) => {
      const salah = $$('input, select, textarea', form).filter((i) => !i.disabled && i.type !== 'hidden' && !tampilkan(i));
      if (salah.length) { e.preventDefault(); e.stopImmediatePropagation(); salah[0].focus(); }
    });
  });

  /* ---------- Dropdown topik bergantung kategori ---------- */
  $$('select[data-topik-untuk]').forEach((sub) => {
    const kat = document.getElementById(sub.dataset.topikUntuk);
    const map = JSON.parse(sub.dataset.peta || '{}');
    const kosong = sub.dataset.kosong || 'Semua topik';
    const isi = () => {
      const pilihan = sub.dataset.terpilih || sub.value;
      const daftar = map[kat.selectedOptions[0]?.dataset.id || kat.value] || [];
      sub.innerHTML = '';
      sub.append(new Option(daftar.length ? kosong : 'Tidak ada topik', ''));
      daftar.forEach((t) => sub.append(new Option(t.nama, t.id, false, String(t.id) === String(pilihan))));
      sub.disabled = !daftar.length;
      sub.dataset.terpilih = '';
    };
    kat.addEventListener('change', isi);
    isi();
  });

  /* ---------- Pratinjau avatar ---------- */
  $$('input[type=file][data-pratinjau]').forEach((input) => {
    input.addEventListener('change', () => {
      const f = input.files[0];
      const img = document.getElementById(input.dataset.pratinjau);
      const info = document.getElementById(input.dataset.info);
      if (!f) return;
      const okTipe = ['image/jpeg', 'image/png', 'image/webp'].includes(f.type);
      const okUkuran = f.size <= 2 * 1024 * 1024;
      if (info) info.textContent = !okTipe ? 'Format harus JPG, PNG, atau WEBP.' : (!okUkuran ? 'Ukuran maksimal 2 MB.' : f.name + ' (' + Math.round(f.size / 1024) + ' KB)');
      if (info) info.className = 'kecil ' + (okTipe && okUkuran ? 'muted' : 'pesan-salah');
      if (okTipe && img) img.src = URL.createObjectURL(f);
    });
  });

  /* ---------- Filter otomatis terkirim saat diubah ---------- */
  $$('form[data-auto-kirim]').forEach((form) => {
    $$('select, input[type=radio], input[type=date]', form).forEach((el) => el.addEventListener('change', () => form.requestSubmit ? form.requestSubmit() : form.submit()));
  });
})();

// Panel filter jelajah: dilipat di layar kecil bila belum ada filter aktif
document.querySelectorAll('details[data-lipat-mobile="1"]').forEach((d) => { if (window.innerWidth <= 1180) d.open = false; });
