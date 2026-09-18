<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'slug', 'sector', 'city', 'logo_path',
    'website', 'phone', 'email', 'founded_on', 'about',
])]
class Company extends Model
{
    protected function casts(): array
    {
        return [
            'founded_on' => 'date',
        ];
    }

    /** Ayni firmadan birden fazla uye olabilir. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
