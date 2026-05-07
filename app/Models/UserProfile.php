<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id',
        'sex',
        'birth_date',
        'height_cm',
        'weight_kg',
        'activity_level',
        'daily_kcal_target',
        'p_pct',
        'f_pct',
        'c_pct',
        'units_pref',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'height_cm' => 'decimal:1',
            'weight_kg' => 'decimal:1',
            'daily_kcal_target' => 'integer',
            'p_pct' => 'integer',
            'f_pct' => 'integer',
            'c_pct' => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
