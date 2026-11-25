<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $kategori = Category::urutNama()
            ->withCount([
                'quizzes as jumlah_kuis' => fn (Builder $q) => $q->where('status', 'terbit'),
                'quizzes as semua_kuis',
            ])
            ->with(['topics' => fn ($q) => $q->withCount('quizzes as jumlah_kuis')])
            ->get();

        return view('admin.kategori', [
            'title' => 'Kategori & Topik',
            'menu' => 'admin-kategori',
            'kategori' => $kategori,
            'errors' => form_errors(),
            'form' => session('form') ?? (string) $request->input('form'),
            'editKat' => $request->filled('edit_kategori') ? Category::find($request->integer('edit_kategori')) : null,
            'editTopik' => $request->filled('edit_topik') ? Subcategory::find($request->integer('edit_topik')) : null,
        ]);
    }

    public function action(Request $request)
    {
        return match ($request->input('aksi')) {
            'simpan-kategori' => $this->simpanKategori($request),
            'hapus-kategori' => $this->hapusKategori($request),
            'simpan-topik' => $this->simpanTopik($request),
            'hapus-topik' => $this->hapusTopik($request),
            default => redirect()->route('admin.kategori'),
        };
    }

    private function simpanKategori(Request $request)
    {
        $id = $request->integer('id');
        $nama = (string) $request->input('nama');
        $slug = Category::slugify($nama);
        $errors = validasi($request->all(), [
            'nama' => 'required|min:2|max:80',
            'warna' => 'required|in:' . implode(',', WARNA),
            'deskripsi' => 'nullable|max:255',
        ], ['nama' => 'Nama kategori', 'warna' => 'Warna', 'deskripsi' => 'Deskripsi']);

        // slug selalu huruf kecil, jadi nama yang hanya beda huruf besar/kecil ikut terdeteksi
        $duplikat = Category::whereKeyNot($id)
            ->where(fn (Builder $q) => $q->where('nama', $nama)->orWhere('slug', $slug))
            ->exists();
        if ($duplikat) $errors['nama'] ??= 'Kategori dengan nama ini sudah ada.';
        if ($errors) return $this->gagal($errors, ['form' => 'kategori']);

        $data = ['nama' => $nama, 'slug' => $slug, 'warna' => $request->input('warna'), 'deskripsi' => $request->input('deskripsi')];
        $kategori = $id ? Category::find($id) : null;
        if ($kategori) {
            $kategori->update($data);
            flash('sukses', 'Kategori diperbarui.');
        } else {
            Category::create($data);
            flash('sukses', 'Kategori "' . $nama . '" ditambahkan.');
        }
        return redirect()->route('admin.kategori');
    }

    private function hapusKategori(Request $request)
    {
        $kategori = Category::find($request->integer('id'));
        if ($kategori && !$kategori->quizzes()->exists()) {
            DB::transaction(function () use ($kategori) {
                $kategori->topics()->delete();
                $kategori->delete();
            });
            flash('sukses', 'Kategori "' . $kategori->nama . '" dihapus.');
        } else {
            flash('error', 'Kategori masih dipakai oleh kuis sehingga tidak dapat dihapus.');
        }
        return redirect()->route('admin.kategori');
    }

    private function simpanTopik(Request $request)
    {
        $id = $request->integer('id');
        $catId = $request->integer('category_id');
        $nama = (string) $request->input('nama');
        $errors = validasi($request->all(), [
            'nama' => 'required|min:2|max:80',
            'category_id' => 'required|integer',
        ], ['nama' => 'Nama topik', 'category_id' => 'Kategori']);
        if (!Category::whereKey($catId)->exists()) $errors['category_id'] ??= 'Kategori tidak ditemukan.';

        $duplikat = Subcategory::where('category_id', $catId)->whereKeyNot($id)->pluck('nama')
            ->contains(fn ($n) => mb_strtolower($n) === mb_strtolower($nama));
        if ($duplikat) $errors['nama'] ??= 'Topik ini sudah ada di kategori tersebut.';
        if ($errors) return $this->gagal($errors, ['form' => 'topik']);

        $topik = $id ? Subcategory::find($id) : null;
        if ($topik) {
            // Topik yang pindah kategori dilepas dari kuis yang kategorinya tidak lagi cocok
            DB::transaction(function () use ($topik, $catId, $nama) {
                $pindah = (int) $topik->category_id !== $catId;
                $topik->update(['nama' => $nama, 'category_id' => $catId]);
                if ($pindah) $topik->quizzes()->update(['subcategory_id' => null]);
            });
            flash('sukses', 'Topik diperbarui.');
        } else {
            Subcategory::create(['category_id' => $catId, 'nama' => $nama]);
            flash('sukses', 'Topik "' . $nama . '" ditambahkan.');
        }
        return redirect()->route('admin.kategori');
    }

    private function hapusTopik(Request $request)
    {
        Subcategory::whereKey($request->integer('id'))->delete();
        flash('sukses', 'Topik dihapus.');
        return redirect()->route('admin.kategori');
    }
}
