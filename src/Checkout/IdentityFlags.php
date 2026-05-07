<?php

declare(strict_types=1);

namespace Svea\Checkout;

/**
 * Controls which identity UI elements are hidden in the checkout iframe.
 *
 * Pass an instance to {@see CheckoutOrder::identityFlags()}.
 *
 * @see https://docs.payments.svea.com/docs/checkout/data-types
 */
final readonly class IdentityFlags
{
    /**
     * @param  bool  $hideNotYou  Hide the "Not you?" link. Default false.
     * @param  bool  $hideChangeAddress  Hide the change-address option. Default false.
     * @param  bool  $hideAnonymous  Hide the anonymous purchase option. Default false.
     */
    public function __construct(
        public bool $hideNotYou = false,
        public bool $hideChangeAddress = false,
        public bool $hideAnonymous = false,
    ) {}

    /**
     * @return array{HideNotYou: bool, HideChangeAddress: bool, HideAnonymous: bool}
     */
    public function toArray(): array
    {
        return [
            'HideNotYou' => $this->hideNotYou,
            'HideChangeAddress' => $this->hideChangeAddress,
            'HideAnonymous' => $this->hideAnonymous,
        ];
    }
}
