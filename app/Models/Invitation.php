<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * DIKKAT: code, used_at ve created_user_id bilerek $fillable disindadir.
 * Kod yalnizca oluster() ile uretilir, kullanim damgasi sunucu tarafinda atilir.
 */
#[Fillable(['type', 'email', 'name', 'event_id', 'expires_at'])]
class Invitation extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    /** Tahmin edilemez davet kodu uretir. */
    public static function yeniKod(): string
    {
        return Str::lower(Str::random(40));
    }

    public function isKullanilabilir(): bool
    {
        if ($this->used_at !== null) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
