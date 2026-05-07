<?php

namespace App\Livewire\Cabinet;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        return view('livewire.cabinet.dashboard', [
            'user' => Auth::user(),
        ])->layout('components.layouts.app', [
            'title' => __('cabinet.dashboard'),
        ]);
    }
}
