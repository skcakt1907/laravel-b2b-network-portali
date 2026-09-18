<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DIKKAT: status, reviewed_by, reviewed_at, admin_note ve created_user_id
 * bilerek $fillable disindadir; bunlari yalnizca admin onay akisi yazar.
 */
#[Fillable([
    'invitation_id', 'name', 'email', 'phone', 'company_name',
    'sector', 'city', 'brand', 'reference_name', 'message',
])]
class MembershipApplication extends Model
{
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function isBeklemede(): bool
    {
        return $this->status === 'beklemede';
    }
}
