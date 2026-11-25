// ZIQO: pemutar kuis. Semua jawaban disimpan ke server; penilaian 100% di server.
(function () {
  'use strict';
  const cfg = JSON.parse(document.getElementById('data-kuis').textContent);
  const soal = cfg.soal;
  const jawaban = {};
  const umpan = {};
  Object.entries(cfg.jawaban || {}).forEach(([k, v]) => { jawaban[k] = { opsi: (v.opsi || []).map(Number), teks: v.teks || '', dikunci: !!v.dikunci }; });
  Object.entries(cfg.umpan || {}).forEach(([k, v]) => { umpan[k] = v; });

  const el = (id) => document.getElementById(id);
  const kartu = el('soal-kartu'), nomor = el('nomor-soal'), boxUmpan = el('umpan-balik');
  const btnPrev = el('tombol-sebelum'), btnNext = el('tombol-lanjut'), btnCek = el('tombol-cek'), btnSelesai = el('tombol-selesai');
  const statusSimpan = el('status-simpan');
  const HURUF = ['A', 'B', 'C', 'D', 'E', 'F'];
  const WARNA = ['w-ungu', 'w-kuning', 'w-hijau', 'w-biru', 'w-pink', 'w-oranye'];

  // Mode berurutan: posisi terjauh yang boleh dibuka = soal pertama yang belum terkunci
  let idx = 0;
  if (!cfg.bebas) { const i = soal.findIndex((s) => !(jawaban[s.id] && jawaban[s.id].dikunci)); idx = i === -1 ? soal.length - 1 : i; }
  let selesai = false;

  const j = (s) => (jawaban[s.id] = jawaban[s.id] || { opsi: [], teks: '', dikunci: false });
  const terjawab = (s) => { const a = jawaban[s.id]; return !!a && (a.opsi.length > 0 || (a.teks || '').trim() !== ''); };

  /* ---------- Komunikasi server ---------- */
  let antrean = Promise.resolve();
  function kirim(data) {
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => {
      if (Array.isArray(v)) v.forEach((x) => fd.append(k + '[]', x)); else if (v !== undefined && v !== null) fd.append(k, v);
    });
    return fetch(cfg.endpoint, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-Token': cfg.csrf } })
      .then(async (r) => {
        let body = {};
        try { body = await r.json(); } catch (e) { body = { ok: false, pesan: 'Respons server tidak valid.' }; }
        if (body.redirect && !r.ok) { selesai = true; window.location.href = body.redirect; throw new Error('redirect'); }
        if (r.status === 401) { window.location.reload(); throw new Error('sesi'); }
        return body;
      });
  }
  function setStatus(t, kelas) { statusSimpan.textContent = t; statusSimpan.style.color = kelas === 'gagal' ? 'var(--merah)' : ''; }
  function simpan(s, kunci) {
    const a = j(s);
    setStatus('Menyimpan…');
    antrean = antrean.then(() => kirim({ aksi: 'simpan', soal: s.id, nilai: ['isian', 'esai'].includes(s.tipe) ? a.teks : a.opsi, kunci: kunci ? '1' : '0' }))
      .then((b) => {
        if (b.ok) { setStatus('✓ Tersimpan'); if (kunci) a.dikunci = true; }
        else { setStatus('Gagal menyimpan', 'gagal'); window.ziqoToast(b.pesan || 'Jawaban gagal disimpan.', 'error'); }
        return b;
      })
      .catch((e) => { if (e.message !== 'redirect' && e.message !== 'sesi') { setStatus('Offline, coba lagi', 'gagal'); } });
    return antrean;
  }
  let tundaTeks = null;

  /* ---------- Render ---------- */
  function renderNomor() {
    nomor.innerHTML = '';
    soal.forEach((s, i) => {
      const b = document.createElement('button');
      b.type = 'button'; b.textContent = i + 1;
      b.setAttribute('aria-label', 'Soal ' + (i + 1) + (terjawab(s) ? ', terjawab' : ', belum dijawab'));
      if (terjawab(s)) b.classList.add('terjawab');
      if (umpan[s.id]) b.classList.add(umpan[s.id].benar ? 'benar' : 'salah');
      if (i === idx) { b.classList.add('aktif'); b.setAttribute('aria-current', 'step'); }
      if (!cfg.bebas && i !== idx) b.disabled = true;
      b.addEventListener('click', () => pindah(i));
      nomor.appendChild(b);
    });
    const n = soal.filter(terjawab).length;
    el('info-progres').textContent = n + '/' + soal.length + ' terjawab';
    el('bar-progres').style.width = (soal.length ? (n / soal.length) * 100 : 0) + '%';
  }

  function buatOpsi(s, a, i, o, jenis, kunciSet) {
    const label = document.createElement('label');
    label.className = 'opsi ' + WARNA[i % WARNA.length] + (jenis === 'checkbox' ? ' kotak' : '');
    const input = document.createElement('input');
    input.type = jenis; input.name = 'soal-' + s.id; input.value = o.id;
    input.checked = a.opsi.includes(o.id);
    input.disabled = a.dikunci;
    const span = document.createElement('span');
    span.dataset.huruf = HURUF[i] || '';
    span.textContent = o.teks;
    if (kunciSet) {
      if (kunciSet.has(o.id)) label.classList.add('benar');
      else if (a.opsi.includes(o.id)) label.classList.add('salah');
    }
    input.addEventListener('change', () => {
      if (jenis === 'radio') a.opsi = [o.id];
      else a.opsi = Array.from(kartu.querySelectorAll('input[name="soal-' + s.id + '"]:checked')).map((x) => Number(x.value));
      simpan(s, false); renderNomor();
    });
    label.append(input, span);
    return label;
  }

  function render() {
    const s = soal[idx], a = j(s), u = umpan[s.id];
    const kunciSet = u ? new Set(u.kunci) : null;
    kartu.innerHTML = '';
    const kepala = document.createElement('div'); kepala.className = 'soal-kepala';
    const kiri = document.createElement('div'); kiri.className = 'baris';
    const c1 = document.createElement('span'); c1.className = 'chip'; c1.textContent = 'Soal ' + (idx + 1) + ' dari ' + soal.length;
    const c2 = document.createElement('span'); c2.className = 'chip chip-garis'; c2.textContent = cfg.tipeLabel[s.tipe];
    kiri.append(c1, c2);
    const c3 = document.createElement('span'); c3.className = 'chip w-kuning'; c3.textContent = s.poin + ' poin';
    kepala.append(kiri, c3);
    const teks = document.createElement('p'); teks.className = 'soal-teks'; teks.id = 'teks-soal'; teks.textContent = s.teks;
    kartu.append(kepala, teks);

    if (s.tipe === 'pg' || s.tipe === 'pgk' || s.tipe === 'bs') {
      const grid = document.createElement('div');
      grid.className = 'opsi-grid' + (s.tipe === 'bs' ? ' bs-grid' : '');
      grid.setAttribute('role', s.tipe === 'pgk' ? 'group' : 'radiogroup');
      grid.setAttribute('aria-labelledby', 'teks-soal');
      s.opsi.forEach((o, i) => grid.appendChild(buatOpsi(s, a, i, o, s.tipe === 'pgk' ? 'checkbox' : 'radio', kunciSet)));
      if (s.tipe === 'pgk') { const h = document.createElement('p'); h.className = 'kecil muted'; h.textContent = 'Pilih semua jawaban yang benar (bisa lebih dari satu).'; kartu.appendChild(h); }
      kartu.appendChild(grid);
    } else {
      const esai = s.tipe === 'esai';
      const f = document.createElement(esai ? 'textarea' : 'input');
      f.className = 'input'; f.id = 'jawaban-teks';
      f.setAttribute('aria-labelledby', 'teks-soal');
      f.maxLength = esai ? 5000 : 200;
      f.placeholder = esai ? 'Tulis jawaban esaimu di sini…' : 'Ketik jawaban singkat…';
      if (esai) f.rows = 8; else f.autocomplete = 'off';
      f.value = a.teks || '';
      f.disabled = a.dikunci;
      if (!esai) f.style.fontSize = '1.1rem';
      const hitung = document.createElement('p'); hitung.className = 'kecil muted mt-1 mb-0';
      const perbarui = () => { hitung.textContent = esai ? f.value.length + '/5000 karakter · dinilai oleh pengajar' : 'Maksimal 200 karakter'; };
      perbarui();
      f.addEventListener('input', () => {
        a.teks = f.value; perbarui(); renderNomor(); setStatus('Mengetik…');
        clearTimeout(tundaTeks); tundaTeks = setTimeout(() => simpan(s, false), 700);
      });
      f.addEventListener('keydown', (e) => { if (!esai && e.key === 'Enter') { e.preventDefault(); btnCek.hidden ? btnNext.click() : btnCek.click(); } });
      kartu.append(f, hitung);
      if (u && s.tipe === 'isian') f.classList.add(u.benar ? 'benar' : 'salah');
    }
    if (a.dikunci && !u) { const k = document.createElement('p'); k.className = 'kecil muted mt-1 mb-0'; k.textContent = '🔒 Jawaban soal ini sudah dikunci.'; kartu.appendChild(k); }

    renderUmpan(s);
    btnPrev.hidden = !cfg.bebas;
    btnPrev.disabled = idx === 0;
    btnNext.hidden = idx === soal.length - 1;
    btnCek.hidden = !(cfg.feedback && s.tipe !== 'esai' && !a.dikunci);
    renderNomor();
  }

  function renderUmpan(s) {
    boxUmpan.innerHTML = '';
    const u = umpan[s.id];
    if (!u) return;
    const d = document.createElement('div'); d.className = 'umpan-balik ' + (u.benar ? 'benar' : 'salah');
    d.innerHTML = u.benar
      ? '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>'
      : '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>';
    const isi = document.createElement('div');
    const judul = document.createElement('b'); judul.textContent = u.benar ? 'Benar! Hebat!' : 'Belum tepat.';
    isi.appendChild(judul);
    if (!u.benar && u.jawaban_diterima && u.jawaban_diterima.length) {
      const p = document.createElement('p'); p.textContent = 'Jawaban yang diterima: ' + u.jawaban_diterima.join(' / '); isi.appendChild(p);
    }
    if (u.pembahasan) { const p = document.createElement('p'); p.textContent = u.pembahasan; isi.appendChild(p); }
    d.appendChild(isi); boxUmpan.appendChild(d);
  }

  /* ---------- Navigasi ---------- */
  async function pindah(i) {
    if (i < 0 || i >= soal.length || i === idx) return;
    clearTimeout(tundaTeks);
    const s = soal[idx];
    if (!cfg.bebas) {
      if (i < idx) return;
      if (!j(s).dikunci) {
        if (!terjawab(s)) {
          const ok = await window.ziqoKonfirmasi({ judul: 'Lewati soal ini?', pesan: 'Soal belum dijawab. Pada kuis ini kamu tidak bisa kembali ke soal sebelumnya.', ok: 'Lewati' });
          if (!ok) return;
        }
        await simpan(s, true);
      }
    } else if (s.tipe === 'isian' || s.tipe === 'esai') {
      if (!j(s).dikunci) simpan(s, false);
    }
    idx = i; render();
    kartu.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  btnPrev.addEventListener('click', () => pindah(idx - 1));
  btnNext.addEventListener('click', () => pindah(idx + 1));

  btnCek.addEventListener('click', async () => {
    const s = soal[idx], a = j(s);
    if (!terjawab(s)) { window.ziqoToast('Pilih atau isi jawaban dulu sebelum dicek.', 'info'); return; }
    btnCek.classList.add('is-loading');
    clearTimeout(tundaTeks);
    await antrean;
    try {
      const b = await kirim({ aksi: 'cek', soal: s.id, nilai: ['isian', 'esai'].includes(s.tipe) ? a.teks : a.opsi });
      if (b.ok) { a.dikunci = true; umpan[s.id] = b; render(); }
      else window.ziqoToast(b.pesan || 'Gagal memeriksa jawaban.', 'error');
    } catch (e) { /* redirect */ }
    btnCek.classList.remove('is-loading');
  });

  async function kumpulkan(otomatis) {
    if (selesai) return;
    if (!otomatis) {
      const kosong = soal.filter((s) => !terjawab(s)).length;
      const ok = await window.ziqoKonfirmasi({
        judul: 'Kumpulkan jawaban?',
        pesan: kosong ? kosong + ' soal belum dijawab dan akan dinilai 0. Setelah dikumpulkan, jawaban tidak bisa diubah.' : 'Semua soal sudah dijawab. Setelah dikumpulkan, jawaban tidak bisa diubah.',
        ok: 'Ya, kumpulkan',
      });
      if (!ok) return;
    }
    selesai = true;
    btnSelesai.classList.add('is-loading');
    clearTimeout(tundaTeks);
    const s = soal[idx];
    if ((s.tipe === 'isian' || s.tipe === 'esai') && !j(s).dikunci) simpan(s, false);
    await antrean;
    try {
      const b = await kirim({ aksi: 'selesai' });
      if (b.redirect) window.location.href = b.redirect;
    } catch (e) { /* sudah diarahkan */ }
  }
  btnSelesai.addEventListener('click', () => kumpulkan(false));

  /* ---------- Timer (patokan waktu dari server) ---------- */
  if (cfg.sisaDetik !== null) {
    const akhir = Date.now() + cfg.sisaDetik * 1000;
    const t = el('timer'), tt = el('timer-teks');
    t.hidden = false;
    const tick = () => {
      const sisa = Math.max(0, Math.round((akhir - Date.now()) / 1000));
      tt.textContent = String(Math.floor(sisa / 60)).padStart(2, '0') + ':' + String(sisa % 60).padStart(2, '0');
      t.setAttribute('aria-label', 'Sisa waktu ' + Math.floor(sisa / 60) + ' menit ' + (sisa % 60) + ' detik');
      t.classList.toggle('genting', sisa <= 60);
      if (sisa === 60) window.ziqoToast('Waktu tersisa 1 menit!', 'info');
      if (sisa <= 0) { clearInterval(iv); window.ziqoToast('Waktu habis! Jawaban dikumpulkan otomatis.', 'info'); kumpulkan(true); }
    };
    const iv = setInterval(tick, 1000); tick();
  }

  document.addEventListener('keydown', (e) => {
    if (e.target.matches('input[type=text], textarea, input:not([type])')) return;
    if (e.key === 'ArrowRight' && !btnNext.hidden) pindah(idx + 1);
    if (e.key === 'ArrowLeft' && cfg.bebas) pindah(idx - 1);
  });

  render();
})();
