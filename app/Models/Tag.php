<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Tag extends Model implements AuditableContract
{
    use Auditable;

    public $timestamps = false;

    protected $fillable = [
        'slug',
        'name',
        'type',
    ];

    public function isDiet(): bool
    {
        return $this->type === 'diet';
    }

    public function isCuisine(): bool
    {
        return $this->type === 'cuisine';
    }

    public function isMisc(): bool
    {
        return $this->type === 'misc';
    }
}
