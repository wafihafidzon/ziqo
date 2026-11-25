<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'poin' => 'integer',
        'urutan' => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answerOptions(): HasMany
    {
        return $this->hasMany(AnswerOption::class)->orderBy('urutan')->orderBy('id');
    }

    /** Alias `answerOptions`, sehingga `$soal['opsi']` tetap berlaku di view dan JSON. */
    public function opsi(): HasMany
    {
        return $this->answerOptions();
    }

    public function scopeUrut(Builder $query): void
    {
        $query->orderBy('urutan')->orderBy('id');
    }

}
