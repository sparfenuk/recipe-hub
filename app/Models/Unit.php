<?php

namespace App\Models;

use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * @property UnitType $type
 */
class Unit extends Model
{
    use HasTranslations;

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'type',
        'to_base_factor',
    ];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'type' => UnitType::class,
            'to_base_factor' => 'decimal:6',
        ];
    }

    public function isMass(): bool
    {
        return $this->type === UnitType::Mass;
    }

    public function isVolume(): bool
    {
        return $this->type === UnitType::Volume;
    }

    public function isCount(): bool
    {
        return $this->type === UnitType::Count;
    }
}
