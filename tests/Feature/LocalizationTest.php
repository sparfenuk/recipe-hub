<?php

use Illuminate\Support\Arr;

test('SetLocale middleware sets locale from query param and redirects', function () {
    $response = $this->get('/login?locale=uk');

    $response->assertRedirect('/login');
    $response->assertCookie('locale', 'uk');
});

test('SetLocale middleware sets locale from cookie', function () {
    $response = $this->withCookie('locale', 'uk')->get('/');

    $response->assertOk();
    $response->assertSee('Українська');
});

test('SetLocale middleware ignores invalid locale', function () {
    $response = $this->get('/?locale=fr');

    $response->assertOk();
    $response->assertSee('English');
});

test('SetLocale middleware falls back to en by default', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('English');
});

test('SetLocale middleware respects Accept-Language header', function () {
    $response = $this->withHeader('Accept-Language', 'uk,en;q=0.9')->get('/');

    $response->assertOk();
    $response->assertSee('Переглянути рецепти');
});

test('cookie locale takes precedence over Accept-Language', function () {
    $response = $this->withCookie('locale', 'en')
        ->withHeader('Accept-Language', 'uk')
        ->get('/');

    $response->assertOk();
    $response->assertSee('Browse Recipes');
});

test('every key in lang/en.json exists in lang/uk.json', function () {
    $en = json_decode(
        (string) file_get_contents(lang_path('en.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    $uk = json_decode(
        (string) file_get_contents(lang_path('uk.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $missing = array_diff_key($en, $uk);

    expect($missing)->toBeEmpty(
        'Missing keys in lang/uk.json: '.implode(', ', array_keys($missing))
    );
});

test('every key in lang/en PHP files exists in lang/uk PHP files', function () {
    $enFiles = glob(lang_path('en/*.php'));

    foreach ($enFiles as $enFile) {
        $filename = basename($enFile);
        $ukFile = lang_path("uk/{$filename}");

        expect(file_exists($ukFile))->toBeTrue("Missing UK file: lang/uk/{$filename}");

        $enKeys = array_keys(Arr::dot(require $enFile));
        $ukKeys = array_keys(Arr::dot(require $ukFile));

        $missing = array_diff($enKeys, $ukKeys);

        expect($missing)->toBeEmpty(
            "Missing keys in lang/uk/{$filename}: ".implode(', ', $missing)
        );
    }
});

test('locale switcher renders on the welcome page', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('English');
    $response->assertSee('Українська');
});

test('login page renders translated when locale is uk', function () {
    $response = $this->withCookie('locale', 'uk')->get('/login');

    $response->assertOk();
    $response->assertSee('Увійдіть у свій акаунт');
});
