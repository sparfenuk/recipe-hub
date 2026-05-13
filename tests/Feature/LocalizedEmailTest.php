<?php

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);
});

test('verification email is sent with current locale', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    app()->setLocale('uk');
    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) {
        return $notification->locale === 'uk';
    });
});

test('verification email body uses ukrainian when locale is uk', function () {
    $user = User::factory()->unverified()->create();

    app()->setLocale('uk');
    $notification = (new VerifyEmail)->locale('uk');
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Підтвердження електронної адреси');
    expect($mail->introLines[0])->toBe('Будь ласка, натисніть кнопку нижче, щоб підтвердити вашу електронну адресу.');
    expect($mail->actionText)->toBe('Підтвердження електронної адреси');
    expect($mail->outroLines[0])->toBe('Якщо ви не створювали акаунт, жодних дій не потрібно.');
});

test('verification email body uses english when locale is en', function () {
    $user = User::factory()->unverified()->create();

    app()->setLocale('en');
    $notification = (new VerifyEmail)->locale('en');
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Verify Email Address');
    expect($mail->introLines[0])->toBe('Please click the button below to verify your email address.');
});

test('password reset email is sent with current locale', function () {
    Notification::fake();
    $user = User::factory()->create();

    app()->setLocale('uk');
    $user->sendPasswordResetNotification('test-token');

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) {
        return $notification->locale === 'uk';
    });
});

test('password reset email body uses ukrainian when locale is uk', function () {
    $user = User::factory()->create();

    app()->setLocale('uk');
    $notification = (new ResetPassword('test-token'))->locale('uk');
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Скидання пароля');
    expect($mail->introLines[0])->toBe('Ви отримали цей лист, тому що ми отримали запит на скидання пароля для вашого акаунту.');
    expect($mail->actionText)->toBe('Скинути пароль');
});

test('welcome email is sent on registration with locale', function () {
    Notification::fake();

    $this->withCookie('locale', 'uk')->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();

    Notification::assertSentTo($user, WelcomeNotification::class, function (WelcomeNotification $notification) {
        return $notification->locale === 'uk';
    });
});

test('welcome email body uses ukrainian when locale is uk', function () {
    $user = User::factory()->create();

    app()->setLocale('uk');
    $notification = (new WelcomeNotification)->locale('uk');
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Ласкаво просимо до Recipe Hub!');
    expect($mail->introLines[0])->toBe('Ваш акаунт успішно створено. Тепер ви можете переглядати рецепти, зберігати обрані та користуватися калькулятором порцій.');
    expect($mail->actionText)->toBe('Почати перегляд');
});

test('welcome email body uses english when locale is en', function () {
    $user = User::factory()->create();

    app()->setLocale('en');
    $notification = (new WelcomeNotification)->locale('en');
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Welcome to Recipe Hub!');
    expect($mail->introLines[0])->toBe('Your account has been created successfully. You can now browse recipes, save favorites, and use the portion calculator.');
    expect($mail->actionText)->toBe('Start Browsing');
});

test('welcome email action url points to recipes page', function () {
    $user = User::factory()->create();

    $notification = new WelcomeNotification;
    $mail = $notification->toMail($user);

    expect($mail->actionUrl)->toContain('/recipes');
});

test('all email translation keys exist in both en and uk', function () {
    $keys = [
        'Hello!',
        'Regards,',
        'Verify Email Address',
        'Please click the button below to verify your email address.',
        'If you did not create an account, no further action is required.',
        'Reset Password Notification',
        'You are receiving this email because we received a password reset request for your account.',
        'Reset Password',
        'This password reset link will expire in :count minutes.',
        'If you did not request a password reset, no further action is required.',
        'Welcome to :app!',
        'Your account has been created successfully. You can now browse recipes, save favorites, and use the portion calculator.',
        'Start Browsing',
    ];

    $en = json_decode((string) file_get_contents(lang_path('en.json')), true);
    $uk = json_decode((string) file_get_contents(lang_path('uk.json')), true);

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($uk)->toHaveKey($key);
        expect($uk[$key])->not->toBe($key);
    }
});
