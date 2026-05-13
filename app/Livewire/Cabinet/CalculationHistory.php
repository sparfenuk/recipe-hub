<?php

namespace App\Livewire\Cabinet;

use App\Models\CalculatorSession;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CalculationHistory extends Component
{
    use WithPagination;

    public function delete(int $sessionId): void
    {
        /** @var User $user */
        $user = Auth::user();

        CalculatorSession::query()
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function render(): View
    {
        return view('livewire.cabinet.calculation-history', [
            'sessions' => $this->getSessions(),
        ])->layout('components.layouts.app', [
            'title' => __('cabinet.calculations'),
        ]);
    }

    /** @return LengthAwarePaginator<int, CalculatorSession> */
    private function getSessions(): LengthAwarePaginator
    {
        /** @var User $user */
        $user = Auth::user();

        return CalculatorSession::query()
            ->where('user_id', $user->id)
            ->with('recipe:id,slug,title')
            ->orderByDesc('created_at')
            ->paginate(12);
    }
}
