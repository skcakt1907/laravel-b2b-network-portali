<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'slug', 'description', 'type', 'category',
    'starts_at', 'ends_at', 'location', 'online_url', 'capacity',
    'visibility', 'cover_path', 'is_published', 'has_presentations',
])]
class Event extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
            'has_presentations' => 'boolean',
        ];
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---------------------------------------------------------------- kapsamlar

    public function scopeYayinda(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeYaklasan(Builder $q): Builder
    {
        return $q->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function scopeGecmis(Builder $q): Builder
    {
        return $q->where('starts_at', '<', now())->orderByDesc('starts_at');
    }

    /** Misafirler yalnizca kendilerine acilmis etkinlikleri gorebilir. */
    public function scopeGorunur(Builder $q, ?User $user): Builder
    {
        if ($user !== null && $user->isMisafir()) {
            return $q->where('visibility', 'uye_misafir');
        }

        return $q;
    }

    // ---------------------------------------------------------------- yardimcilar

    public function katilanSayisi(): int
    {
        return $this->attendees()->where('rsvp', 'katiliyor')->count();
    }

    public function kontenjanDoluMu(): bool
    {
        return $this->capacity !== null && $this->katilanSayisi() >= $this->capacity;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
