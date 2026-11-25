<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'durasi_menit' => 'integer',
        'tampilkan_feedback' => 'boolean',
        'navigasi_bebas' => 'boolean',
    ];

    /** publik = tampil di Jelajah; room = tersembunyi, hanya bisa dikerjakan lewat kode room. */
    public const AKSES = ['publik' => 'Publik', 'room' => 'Hanya lewat room'];

    public function hanyaRoom(): bool
    {
        return $this->akses === 'room';
    }

    public const SORT = [
        'terbaru' => 'Terbaru',
        'populer' => 'Populer',
        'rekomendasi' => 'Rekomendasi untukmu',
        'judul' => 'Judul A–Z',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('urutan')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function scopeTerbit(Builder $query): void
    {
        $query->where('status', 'terbit');
    }

    /** Atribut kartu: kategori, kategori_slug, warna, topik, pembuat, jumlah_soal, jumlah_esai, jumlah_percobaan. */
    public function scopeWithCard(Builder $query): void
    {
        $query->select('quizzes.*')
            ->addSelect([
                'kategori' => Category::select('nama')->whereColumn('categories.id', 'quizzes.category_id'),
                'kategori_slug' => Category::select('slug')->whereColumn('categories.id', 'quizzes.category_id'),
                'warna' => Category::select('warna')->whereColumn('categories.id', 'quizzes.category_id'),
                'topik' => Subcategory::select('nama')->whereColumn('subcategories.id', 'quizzes.subcategory_id'),
                'pembuat' => User::select('nama')->whereColumn('users.id', 'quizzes.user_id'),
            ])
            ->withCount([
                'questions as jumlah_soal',
                'questions as jumlah_esai' => fn (Builder $q) => $q->where('tipe', 'esai'),
                'attempts as jumlah_percobaan' => fn (Builder $q) => $q->where('status', '!=', 'berlangsung'),
            ]);
    }
}
