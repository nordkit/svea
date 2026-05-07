# Error Handling

The SDK uses a typed exception hierarchy:

```
SveaException                            (base)
├── SveaApiException                     (any non-2xx — ->statusCode, ->sveaCode, ->sveaMessage, ->getLastResponse())
│   ├── SveaAuthenticationException      (401)
│   ├── SveaInvalidRequestException      (400 — ->errors[])
│   ├── SveaNotFoundException            (404)
│   └── SveaRateLimitException           (429 — auto-retried if max_retries > 0)
├── SveaConnectionException              (network failure / timeout — auto-retried)
└── SignatureVerificationException        (inbound webhook HMAC mismatch)
```

## Catching errors

```php
use Svea\Exceptions\SveaApiException;
use Svea\Exceptions\SveaNotFoundException;

try {
    $order = Svea::admin()->order('12345678')->get();
} catch (SveaNotFoundException $e) {
    // 404 — order not found
} catch (SveaApiException $e) {
    $e->statusCode;        // int
    $e->sveaCode;          // string|null
    $e->sveaMessage;       // string|null
    $e->getLastResponse(); // SveaResponse — raw body, headers, status
}
```

