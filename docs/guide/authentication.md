# Authentication

## Outbound API requests

All three outbound APIs (Checkout, Admin, Subscriptions) use Svea's **HMAC-SHA512** digest:

```
Authorization: SveaCheckoutGateway {merchantId} {base64(sha512(body + sharedSecret))}
```

`SveaConnector` computes and attaches this header automatically on every request using `merchant_id` and `shared_secret` from config.

## Inbound webhook verification

`webhook_secret` is a **separate** secret used only to verify the `Svea-Signature` header on inbound webhook pushes — it is **not** the same as `shared_secret`.

```
Svea-Signature: HMAC-SHA256(raw body, webhook_secret)
```

See [Webhooks](../api/webhooks) for the verification API.

