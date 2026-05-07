<?php

declare(strict_types=1);
use Svea\Exceptions\SignatureVerificationException;
use Svea\Subscriptions\EventType;
use Svea\Webhooks\Webhook;
use Svea\Webhooks\WebhookEvent;

test('constructEvent returns WebhookEvent for valid signature', function () {
    $payload = json_encode(['EventType' => 'CheckoutOrder.Created', 'OrderId' => '12345678']);
    $secret = 'webhook-secret';
    $sig = hash_hmac('sha256', $payload, $secret);
    $event = Webhook::constructEvent($payload, $sig, $secret);
    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->type())->toBe(EventType::CheckoutOrderCreated)
        ->and($event->orderId())->toBe('12345678');
});

test('constructEvent throws on invalid signature', function () {
    $payload = json_encode(['EventType' => 'Payment.Delivered', 'OrderId' => '123']);
    expect(fn () => Webhook::constructEvent($payload, 'bad-sig', 'secret'))
        ->toThrow(SignatureVerificationException::class);
});
