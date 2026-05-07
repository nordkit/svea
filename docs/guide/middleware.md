# Custom Middleware

`SveaClient` accepts an optional Guzzle `HandlerStack`, letting you push any middleware — logging, tracing, metrics, mock handlers.

## Generic middleware

```php
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Svea\SveaClient;

$stack = HandlerStack::create();
$stack->push(Middleware::log($logger, $messageFormatter));

$svea = new SveaClient(
    config: config('svea'),
    handlerStack: $stack,
);
```

## HTTP tracing with Wiretap

[`nordkit/wiretap`](https://github.com/nordkit/wiretap) is a framework-agnostic HTTP tracer that records headers, payloads, status codes, and timing — with built-in filtering and redaction.

```php
use GuzzleHttp\HandlerStack;
use Nordkit\Wiretap\Guzzle\WiretapMiddleware;
use Nordkit\Wiretap\Wiretap;
use Svea\SveaClient;

// In your AppServiceProvider::register():
$this->app->singleton(SveaClient::class, function ($app): SveaClient {
    $stack = HandlerStack::create();
    $stack->push(WiretapMiddleware::make($app->make(Wiretap::class)));

    return new SveaClient(
        config: (array) $app['config']['svea'],
        handlerStack: $stack,
    );
});
```

