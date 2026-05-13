<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FavoriteButton extends Component
{
    public int $recipeId;

    public bool $isFavorited = false;

    public function mount(int $recipeId): void
    {
        $this->recipeId = $recipeId;

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $this->isFavorited = $user->favorites()->where('recipe_id', $this->recipeId)->exists();
        }
    }

    public function toggle(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'));

            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if ($this->isFavorited) {
            $user->favorites()->detach($this->recipeId);
            $this->isFavorited = false;
        } else {
            $user->favorites()->attach($this->recipeId);
            $this->isFavorited = true;
        }

        $this->dispatch('favorite-toggled', recipeId: $this->recipeId);
    }

    public function render(): View
    {
        return view('livewire.favorite-button');
    }
}
