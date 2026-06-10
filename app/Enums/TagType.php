<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TagType: string implements HasColor, HasLabel
{
    case Diet = 'diet';
    case Cuisine = 'cuisine';
    case Misc = 'misc';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Diet => 'success',
            self::Cuisine => 'info',
            self::Misc => 'gray',
        };
    }
}
