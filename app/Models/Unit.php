<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

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
            'to_base_factor' => 'decimal:6',
        ];
    }

    public function isMass(): bool
    {
        return $this->type === 'mass';
    }

    public function isVolume(): bool
    {
        return $this->type === 'volume';
    }

    public function isCount(): bool
    {
        return $this->type === 'count';
    }
}
