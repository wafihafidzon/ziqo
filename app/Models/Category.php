<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['nama', 'slug', 'warna', 'deskripsi'];

    public function topics(): HasMany
    {
        return $this->hasMany(Subcategory::class)->orderBy('nama');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class);
    }

    public static function slugify(string $nama): string
    {
        $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $nama), '-'));
        return $s !== '' ? $s : 'kategori';
    }

    public function scopeUrutNama(Builder $query): void
    {
        $query->orderBy('nama');
    }

    /** Kategori + jumlah kuis terbit yang tampil di Jelajah (publik) sebagai `jumlah_kuis`. */
    public static function withPublishedCount(): Collection
    {
        return static::urutNama()
            ->withCount(['quizzes as jumlah_kuis' => fn (Builder $q) => $q->where('status', 'terbit')->where('akses', 'publik')])
            ->get();
    }

    /** category_id => daftar topik, untuk dropdown bertingkat di form kuis. */
    public static function topicMap(): array
    {
        return Subcategory::orderBy('nama')->get(['id', 'category_id', 'nama'])
            ->groupBy('category_id')
            ->map(fn ($topik) => $topik->map(fn ($t) => ['id' => $t->id, 'nama' => $t->nama])->values())
            ->toArray();
    }
}
