// Tombol mata: tampilkan / sembunyikan password
document.querySelectorAll('.toggle-mata').forEach((tombol) => {
  tombol.addEventListener('click', () => {
    const input = tombol.closest('.input-pil').querySelector('input');
    const tampil = input.type === 'password';
    input.type = tampil ? 'text' : 'password';
    tombol.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
  });
});
