<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'type',
        'to_base_factor',
    ];

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
