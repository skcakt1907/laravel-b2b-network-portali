<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * DIKKAT: role, status, approved_at, approved_by, guest_expires_at gibi
 * korumali alanlar bilerek $fillable DISINDA birakilmistir. Bunlari asla
 * create($request->all()) ile yazmayin; sunucu tarafinda acikca atayin.
 */
#[Fillable(['name', 'email', 'password', 'title', 'phone', 'company_id', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROL_ADMIN = 'admin';
    public const ROL_UYE = 'uye';
    public const ROL_MISAFIR = 'misafir';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'approved_at' => 'datetime',
            'guest_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------- iliskiler

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function coinTransactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class);
    }

    // ---------------------------------------------------------------- yardimcilar

    public function isAdmin(): bool
    {
        return $this->role === self::ROL_ADMIN;
    }

    public function isUye(): bool
    {
        return $this->role === self::ROL_UYE;
    }

    public function isMisafir(): bool
    {
        return $this->role === self::ROL_MISAFIR;
    }

    /** Hesap giris yapabilecek durumda mi (onayli ve dondurulmamis). */
    public function isAktif(): bool
    {
        if ($this->status !== 'aktif') {
            return false;
        }

        // Suresi dolmus misafir hesabi artik giris yapamaz
        return ! ($this->isMisafir()
            && $this->guest_expires_at !== null
            && $this->guest_expires_at->isPast());
    }

    /**
     * Unitycoin bakiyesi islem defterinden HESAPLANIR.
     * Hicbir yerde ayri bir bakiye sutunu tutulmaz.
     */
    public function coinBalance(): int
    {
        return (int) $this->coinTransactions()->sum('amount');
    }
}
