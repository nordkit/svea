<?php

declare(strict_types=1);

namespace Svea\Checkout;

/**
bl * Fluent builder for Svea Checkout merchant settings.
 *
 * All four URI fields are required by the Svea API. The preferred usage is via
 * the fluent callback on {@see CheckoutOrder::merchantSettings()}:
 * ```php
 * $order->merchantSettings(fn (MerchantSettings $s) => $s
 *     ->pushUri(route('svea.push'))
 *     ->termsUri(route('terms'))
 *     ->confirmationUri(route('checkout.confirmed'))
 *     ->checkoutUri(route('checkout')));
 * ```
 *
 * Or construct directly and pass the instance:
 * ```php
 * $order->merchantSettings(new MerchantSettings(
 *     pushUri: route('svea.push'),
 *     termsUri: route('terms'),
 *     confirmationUri: route('checkout.confirmed'),
 *     checkoutUri: route('checkout'),
 * ));
 * ```
 *
 * @see https://docs.payments.svea.com/docs/checkout/data-types
 */
final class MerchantSettings
{
    /** @var array<string, mixed> */
    private array $data = [];

    /**
ank(     * @param  string|null  $pushUri  Required. URI Svea POSTs order status changes to.
     * @param  string|null  $termsUri  Required. URI to your terms and conditions page.
     * @param  string|null  $confirmationUri  Required. Redirect URI after successful payment.
     * @param  string|null  $checkoutUri  Required. URI of the page hosting the checkout iframe.
     * @param  string|null  $webhookUri  Optional. URI called by shipping services. Max length: 500.
     * @param  string|null  $checkoutValidationCallbackUri  Optional. URI for stock validation callbacks.
     * @param  int[]  $activePartPaymentCampaigns  Optional. Limits available part-payment campaigns to this list.
     * @param  int|null  $promotedPartPaymentCampaign  Optional. Campaign ID to list first in payment method lists.
     * @param  bool  $useClientSideValidation  Optional. Enables client-side validation via the order.validationCallback widget event.
     * @param  bool  $requireClientSideValidationResponse  Optional. When true, checkout awaits a client-side validation response instead of auto-proceeding after 10 s.
     */
    public function __construct(
        ?string $pushUri = null,
        ?string $termsUri = null,
        ?string $confirmationUri = null,
        ?string $checkoutUri = null,
        ?string $webhookUri = null,
        ?string $checkoutValidationCallbackUri = null,
        array $activePartPaymentCampaigns = [],
        ?int $promotedPartPaymentCampaign = null,
        bool $useClientSideValidation = false,
        bool $requireClientSideValidationResponse = false,
    ) {
        if ($pushUri !== null) {
            $this->pushUri($pushUri);
        }
        if ($termsUri !== null) {
            $this->termsUri($termsUri);
        }
        if ($confirmationUri !== null) {
            $this->confirmationUri($confirmationUri);
        }
        if ($checkoutUri !== null) {
            $this->checkoutUri($checkoutUri);
        }
        if ($webhookUri !== null) {
            $this->webhookUri($webhookUri);
        }
        if ($checkoutValidationCallbackUri !== null) {
            $this->checkoutValidationCallbackUri($checkoutValidationCallbackUri);
        }
        if ($activePartPaymentCampaigns !== []) {
            $this->activePartPaymentCampaigns($activePartPaymentCampaigns);
        }
        if ($promotedPartPaymentCampaign !== null) {
            $this->promotedPartPaymentCampaign($promotedPartPaymentCampaign);
        }
        if ($useClientSideValidation) {
            $this->useClientSideValidation(true);
        }
        if ($requireClientSideValidationResponse) {
            $this->requireClientSideValidationResponse(true);
        }
    }

    /**
     * Create a new instance for the fluent callback API.
     *
     * Equivalent to `new static()` — provided as a named constructor for
     * clarity at call sites inside {@see CheckoutOrder::merchantSettings()}.
     *
     * @internal
     */
    public static function make(): static
    {
        return new self;
    }

    /**
     * Set the URI Svea POSTs order status changes to.
     */
    public function pushUri(string $uri): static
    {
        $this->data['PushUri'] = $uri;

        return $this;
    }

    /**
     * Set the URI to your terms and conditions page.
     */
    public function termsUri(string $uri): static
    {
        $this->data['TermsUri'] = $uri;

        return $this;
    }

    /**
     * Set the redirect URI after successful payment.
     */
    public function confirmationUri(string $uri): static
    {
        $this->data['ConfirmationUri'] = $uri;

        return $this;
    }

    /**
     * Set the URI of the page hosting the checkout iframe.
     */
    public function checkoutUri(string $uri): static
    {
        $this->data['CheckoutUri'] = $uri;

        return $this;
    }

    /**
     * Set the URI called by shipping services (max 500 chars).
     */
    public function webhookUri(string $uri): static
    {
        $this->data['WebhookUri'] = $uri;

        return $this;
    }

    /**
     * Set the URI for stock validation callbacks.
     */
    public function checkoutValidationCallbackUri(string $uri): static
    {
        $this->data['CheckoutValidationCallBackUri'] = $uri;

        return $this;
    }

    /**
     * Limit available part-payment campaigns to the given list of campaign IDs.
     *
     * @param  int[]  $campaignIds
     */
    public function activePartPaymentCampaigns(array $campaignIds): static
    {
        $this->data['ActivePartPaymentCampaigns'] = $campaignIds;

        return $this;
    }

    /**
     * Set the campaign ID to list first in payment method lists.
     */
    public function promotedPartPaymentCampaign(int $campaignId): static
    {
        $this->data['PromotedPartPaymentCampaign'] = $campaignId;

        return $this;
    }

    /**
     * Enable client-side validation via the order.validationCallback widget event.
     */
    public function useClientSideValidation(bool $use = true): static
    {
        $this->data['UseClientSideValidation'] = $use;

        return $this;
    }

    /**
     * When true, checkout awaits a client-side validation response instead of
     * auto-proceeding after 10 s. Requires {@see useClientSideValidation()} to be enabled.
     */
    public function requireClientSideValidationResponse(bool $require = true): static
    {
        $this->data['RequireClientSideValidationResponse'] = $require;

        return $this;
    }

    /**
     * Compile into the array payload expected by the Svea Checkout API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
