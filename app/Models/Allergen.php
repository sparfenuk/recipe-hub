<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Translatable\HasTranslations;

class Allergen extends Model implements AuditableContract
{
    use Auditable, HasTranslations;

    public $timestamps = false;

    protected $fillable = [
        'slug',
        'name',
    ];

    /** @var array<int, string> */
    public array $translatable = ['name'];
}
