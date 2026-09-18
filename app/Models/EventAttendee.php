<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DIKKAT: presentation_order ve attended bilerek $fillable disindadir;
 * sunum sirasini sistem uretir, yoklamayi admin isaretler.
 */
#[Fillable(['event_id', 'user_id', 'rsvp', 'responded_at'])]
class EventAttendee extends Model
{
    protected function casts(): array
    {
        return [
            'attended' => 'boolean',
            'responded_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function katiliyorMu(): bool
    {
        return $this->rsvp === 'katiliyor';
    }
}
