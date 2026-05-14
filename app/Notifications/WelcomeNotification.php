<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class WelcomeNotification extends Notification
{
    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(Lang::get('Welcome to :app!', ['app' => config('app.name')]))
            ->line(Lang::get('Your account has been created successfully. You can now browse recipes, save favorites, and use the portion calculator.'))
            ->action(Lang::get('Start Browsing'), url('/recipes'));
    }
}
