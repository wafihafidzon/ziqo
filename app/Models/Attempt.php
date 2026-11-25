<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    protected $table = 'quiz_attempts';

    public $timestamps = false;

    protected $guarded = ['id'];

    // mulai_pada/selesai_pada/diarsipkan_pada stay strings: tanggal() and the deadline math expect 'Y-m-d H:i:s'.
    protected function casts(): array
    {
        return [
            'diarsipkan' => 'boolean',
            'durasi_detik' => 'integer',
            'poin_didapat' => 'decimal:2',
            'poin_maks' => 'decimal:2',
            'skor' => 'decimal:2',
            'jumlah_benar' => 'integer',
            'jumlah_salah' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(UserAnswer::class, 'attempt_id');
    }

    /** Badge yang diperoleh saat percobaan ini selesai. */
    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges', 'attempt_id', 'badge_id')->orderBy('badges.id');
    }

    public function scopeNotRunning(Builder $query): void
    {
        $query->where('status', '<>', 'berlangsung');
    }

    public function scopeUnarchived(Builder $query): void
    {
        $query->where('diarsipkan', false);
    }

    /** Id opsi kunci (benar = 1) dari daftar opsi berbentuk array atau model. */
    public static function kunci(iterable $opsi): array
    {
        return collect($opsi)->filter(fn ($o) => (int) $o['benar'] === 1)
            ->map(fn ($o) => (int) $o['id'])->values()->all();
    }
}
