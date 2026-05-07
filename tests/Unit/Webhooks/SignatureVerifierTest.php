<?php

declare(strict_types=1);

use Svea\Exceptions\SignatureVerificationException;
use Svea\Webhooks\SignatureVerifier;

test('verify passes with correct HMAC signature', function (): void {
    $payload = '{"EventType":"Payment.Delivered","OrderId":"12345678"}';
    $secret = 'my-webhook-secret';
    $signature = hash_hmac('sha256', $payload, $secret);

    expect(fn () => (new SignatureVerifier)->verify($payload, $signature, $secret))
        ->not->toThrow(SignatureVerificationException::class);
});

test('verify throws on signature mismatch', function (): void {
    expect(fn () => (new SignatureVerifier)->verify('payload', 'bad-sig', 'secret'))
        ->toThrow(SignatureVerificationException::class);
});

test('verify throws when signature is empty', function (): void {
    expect(fn () => (new SignatureVerifier)->verify('payload', '', 'secret'))
        ->toThrow(SignatureVerificationException::class);
});

test('verify is case-sensitive on the signature', function (): void {
    $payload = '{"foo":"bar"}';
    $secret = 'secret';
    $signature = hash_hmac('sha256', $payload, $secret);

    expect(fn () => (new SignatureVerifier)->verify($payload, strtoupper($signature), $secret))
        ->toThrow(SignatureVerificationException::class);
});
