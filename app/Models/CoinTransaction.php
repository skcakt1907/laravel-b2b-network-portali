<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Unitycoin islem defteri. Kayitlar DEGISTIRILMEZ ve SILINMEZ;
 * bir hata olursa ters yonde 'duzeltme' kaydi atilir.
 *
 * DIKKAT: amount ve created_by bilerek $fillable disindadir.
 */
#[Fillable(['user_id', 'type', 'reason', 'description'])]
class CoinTransaction extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Islemi doguran kayit (etkinlik, basvuru vb.). */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function isKazanim(): bool
    {
        return $this->amount > 0;
    }
}
