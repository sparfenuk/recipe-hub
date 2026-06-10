<?php

use App\Support\RichText;

it('keeps allowed formatting tags', function () {
    $out = RichText::sanitize('<p>Hello <strong>world</strong> and <em>friends</em></p>');

    expect($out)->toContain('<p>')
        ->and($out)->toContain('<strong>world</strong>')
        ->and($out)->toContain('<em>friends</em>');
});

it('removes script tags and their content', function () {
    $out = RichText::sanitize('<script>alert(1)</script><p>ok</p>');

    expect($out)->toContain('<p>ok</p>')
        ->and($out)->not->toContain('script')
        ->and($out)->not->toContain('alert(1)');
});

it('unwraps disallowed tags but keeps their text', function () {
    $out = RichText::sanitize('<div onclick="x()">kept <span>text</span></div>');

    expect($out)->toContain('kept')
        ->and($out)->toContain('text')
        ->and($out)->not->toContain('<div')
        ->and($out)->not->toContain('<span')
        ->and($out)->not->toContain('onclick');
});

it('strips event-handler and style attributes from allowed tags', function () {
    $out = RichText::sanitize('<p onclick="evil()" style="color:red">hi</p>');

    expect($out)->toContain('hi')
        ->and($out)->not->toContain('onclick')
        ->and($out)->not->toContain('style');
});

it('keeps safe links and hardens them', function () {
    $out = RichText::sanitize('<a href="https://example.com">link</a>');

    expect($out)->toContain('href="https://example.com"')
        ->and($out)->toContain('rel="nofollow noopener noreferrer"')
        ->and($out)->toContain('target="_blank"');
});

it('drops javascript hrefs but keeps the link text', function () {
    $out = RichText::sanitize('<a href="javascript:alert(1)">click</a>');

    expect($out)->not->toContain('javascript')
        ->and($out)->toContain('click');
});

it('preserves utf-8 content', function () {
    expect(RichText::sanitize('<p>Привіт, світ</p>'))->toContain('Привіт, світ');
});

it('returns empty string for null or blank input', function () {
    expect(RichText::sanitize(null))->toBe('')
        ->and(RichText::sanitize('   '))->toBe('');
});
