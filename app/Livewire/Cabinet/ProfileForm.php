<?php

namespace App\Livewire\Cabinet;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ProfileForm extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $units_pref = 'metric';

    /** @var TemporaryUploadedFile|null */
    public $avatar = null;

    public ?string $currentAvatarUrl = null;

    public bool $saved = false;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->name = $user->name;
        $this->currentAvatarUrl = $user->avatar_path
            ? Storage::disk('public')->url($user->avatar_path)
            : null;

        $profile = $user->profile;
        if ($profile) {
            $this->units_pref = $profile->units_pref ?? 'metric';
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'units_pref' => ['required', 'in:metric,imperial'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        /** @var User $user */
        $user = Auth::user();

        $user->name = $this->name;

        if ($this->avatar) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $path = $this->avatar->store('avatars', 'public');
            $user->avatar_path = $path;
            $this->currentAvatarUrl = Storage::disk('public')->url($path);
        }

        $user->save();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['units_pref' => $this->units_pref],
        );

        $this->avatar = null;
        $this->saved = true;
    }

    public function removeAvatar(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
            $this->currentAvatarUrl = null;
        }

        $this->avatar = null;
    }

    public function render(): View
    {
        return view('livewire.cabinet.profile-form')
            ->layout('components.layouts.app', [
                'title' => __('cabinet.profile'),
            ]);
    }
}
