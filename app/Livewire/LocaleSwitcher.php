<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class LocaleSwitcher extends Component
{
    /** @var array<string, string> */
    private const LOCALES = [
        'en' => 'English',
        'uk' => 'Українська',
    ];

    public function render(): View
    {
        return view('livewire.locale-switcher', [
            'currentLocale' => app()->getLocale(),
            'locales' => self::LOCALES,
        ]);
    }
}
