<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Allergen extends Model implements AuditableContract
{
    use Auditable;

    public $timestamps = false;

    protected $fillable = [
        'slug',
        'name',
    ];
}
