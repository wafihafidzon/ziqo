// Editor opsi soal: tambah/hapus baris opsi dan jaga indeks kunci tetap sesuai posisi.
(function () {
  'use strict';
  const editor = document.getElementById('opsi-editor');
  if (editor) {
    const tipe = editor.dataset.tipe;
    const nomori = () => {
      editor.querySelectorAll('.baris-opsi').forEach((b, i) => {
        b.querySelector('input[name^="kunci"]').value = i;
        const t = b.querySelector('input[name="opsi[]"]');
        t.placeholder = 'Opsi ' + String.fromCharCode(65 + i);
        t.setAttribute('aria-label', 'Teks opsi ' + (i + 1));
      });
      const n = editor.children.length;
      document.getElementById('tambah-opsi').disabled = n >= 6;
      editor.querySelectorAll('[data-hapus-opsi]').forEach((b) => { b.disabled = n <= 2; });
    };
    document.getElementById('tambah-opsi').addEventListener('click', () => {
      if (editor.children.length >= 6) return;
      const baru = editor.lastElementChild.cloneNode(true);
      baru.querySelector('input[name="opsi[]"]').value = '';
      baru.querySelector('input[name^="kunci"]').checked = false;
      editor.appendChild(baru); nomori();
      baru.querySelector('input[name="opsi[]"]').focus();
    });
    editor.addEventListener('click', (e) => {
      const b = e.target.closest('[data-hapus-opsi]');
      if (b && editor.children.length > 2) { b.closest('.baris-opsi').remove(); nomori(); }
    });
    nomori();
    void tipe;
  }

  const jEditor = document.getElementById('jawaban-editor');
  if (jEditor) {
    const atur = () => {
      jEditor.querySelectorAll('[data-hapus-opsi]').forEach((b) => { b.disabled = jEditor.children.length <= 1; });
      document.getElementById('tambah-jawaban').disabled = jEditor.children.length >= 8;
    };
    document.getElementById('tambah-jawaban').addEventListener('click', () => {
      const baru = jEditor.lastElementChild.cloneNode(true);
      baru.querySelector('input').value = '';
      jEditor.appendChild(baru); atur(); baru.querySelector('input').focus();
    });
    jEditor.addEventListener('click', (e) => {
      const b = e.target.closest('[data-hapus-opsi]');
      if (b && jEditor.children.length > 1) { b.closest('.baris-opsi').remove(); atur(); }
    });
    atur();
  }
})();
