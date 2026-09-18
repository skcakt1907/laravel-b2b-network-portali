<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'brand', 'services_pitch', 'seeking_pitch', 'birthday',
    'linkedin', 'instagram', 'website', 'whatsapp', 'is_listed',
])]
class Profile extends Model
{
    public const MARKA_ETIKETLERI = [
        'dnkreatif' => 'DN Kreatif',
        'tatilimsensin' => 'Tatilim Sensin',
        'ikisi' => 'DN Kreatif + Tatilim Sensin',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'is_listed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Profil kartinda gosterilecek marka rozeti metni. */
    public function brandLabel(): ?string
    {
        return self::MARKA_ETIKETLERI[$this->brand] ?? null;
    }
}
