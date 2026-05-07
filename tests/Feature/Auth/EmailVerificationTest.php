<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/email/verify');

    $response->assertStatus(200);
});

test('unverified user is redirected to verification notice', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/cabinet');

    $response->assertRedirect('/email/verify');
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake([Verified::class]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('cabinet').'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification email can be resent', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->post('/email/verification-notification');

    $response->assertSessionHas('status', 'verification-link-sent');
});
